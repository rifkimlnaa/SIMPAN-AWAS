<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use Throwable;

class BackupStorageUsageService
{
    public function snapshot(bool $fresh = false): array
    {
        $callback = fn () => $this->buildSnapshot();
        if ($fresh) {
            Cache::forget('backup.storage.usage.v1');
        }
        return Cache::remember('backup.storage.usage.v1', now()->addSeconds(45), $callback);
    }

    private function buildSnapshot(): array
    {
        $privateRoot = rtrim((string) config('filesystems.disks.local.root', storage_path('app/private')), '/\\');
        $publicRoot = rtrim((string) config('filesystems.disks.public.root', storage_path('app/public')), '/\\');
        $backupDisk = (string) config('backup.disk', 'local');
        $backupPath = trim((string) config('backup.path', 'backups'), '/');

        $archiveBytes = $this->directorySize($privateRoot.'/arsip');
        $publicBytes = $this->directorySize($publicRoot);
        $privateBytes = $this->directorySize($privateRoot);
        $backupBytes = $backupDisk === 'local'
            ? $this->directorySize($privateRoot.'/'.$backupPath)
            : $this->remoteDiskSize($backupDisk, $backupPath);
        $databaseBytes = $this->databaseSize();
        $otherPrivateBytes = max(0, $privateBytes - $archiveBytes - ($backupDisk === 'local' ? $backupBytes : 0));
        $applicationBytes = $archiveBytes + $publicBytes + $databaseBytes + $otherPrivateBytes + ($backupDisk === 'local' ? $backupBytes : 0);

        $serverTotal = @disk_total_space(storage_path());
        $serverFree = @disk_free_space(storage_path());
        $server = is_numeric($serverTotal) && is_numeric($serverFree) ? [
            'total_bytes' => (int) $serverTotal,
            'free_bytes' => (int) $serverFree,
            'used_bytes' => max(0, (int) $serverTotal - (int) $serverFree),
        ] : null;

        return [
            'checked_at' => now()->timezone('Asia/Jakarta')->toIso8601String(),
            'application' => [
                'total_bytes' => $applicationBytes,
                'database_bytes' => $databaseBytes,
                'archive_bytes' => $archiveBytes,
                'public_media_bytes' => $publicBytes,
                'backup_bytes' => $backupBytes,
                'other_private_bytes' => $otherPrivateBytes,
                'backup_disk' => $backupDisk,
            ],
            'cpanel' => $this->cpanelQuota(),
            'server_volume' => $server,
        ];
    }

    private function cpanelQuota(): array
    {
        $baseUrl = rtrim((string) config('backup.cpanel.api_url', ''), '/');
        $username = (string) config('backup.cpanel.username', '');
        $token = (string) config('backup.cpanel.api_token', '');
        if ($baseUrl === '' || $username === '' || $token === '') {
            return ['configured' => false, 'available' => false, 'message' => 'API cPanel belum dikonfigurasi.'];
        }
        if (strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)) !== 'https'
            || ! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            return ['configured' => true, 'available' => false, 'message' => 'BACKUP_CPANEL_API_URL harus menggunakan URL HTTPS yang valid.'];
        }

        try {
            $response = Http::withHeaders(['Authorization' => 'cpanel '.$username.':'.$token])
                ->acceptJson()->timeout(12)->get($baseUrl.'/execute/StatsBar/get_stats', ['display' => 'diskusage']);
            if (! $response->successful()) {
                return ['configured' => true, 'available' => false, 'message' => 'API cPanel tidak dapat mengambil statistik saat ini.'];
            }

            $json = $response->json();
            $items = data_get($json, 'result.data')
                ?? data_get($json, 'data.result.data')
                ?? data_get($json, 'data.uapi.result.data')
                ?? [];
            if (is_array($items) && isset($items['id'])) $items = [$items];
            $disk = collect(is_array($items) ? $items : [])->first(fn ($item) => ($item['id'] ?? null) === 'diskusage');
            if (! is_array($disk)) {
                return ['configured' => true, 'available' => false, 'message' => 'Data kuota disk tidak disediakan oleh API cPanel.'];
            }

            $used = $this->parseQuantity((string) ($disk['count'] ?? ''));
            $totalRaw = trim((string) ($disk['max'] ?? ''));
            $total = $this->parseQuantity($totalRaw);
            $unlimited = in_array(strtolower($totalRaw), ['unlimited', '∞'], true);
            if ($used === null) {
                return ['configured' => true, 'available' => false, 'message' => 'Format statistik disk cPanel tidak dikenali.'];
            }

            return [
                'configured' => true,
                'available' => true,
                'used_bytes' => $used,
                'total_bytes' => $total,
                'free_bytes' => $total === null ? null : max(0, $total - $used),
                'percent' => $total && $total > 0 ? min(100, round(($used / $total) * 100, 1)) : null,
                'unlimited' => $unlimited,
                'source' => 'cPanel UAPI StatsBar/get_stats',
                'message' => $unlimited
                    ? 'Kuota hosting dilaporkan unlimited oleh cPanel.'
                    : ($total === null ? 'cPanel tidak melaporkan batas kuota akun.' : 'Data kuota akun hosting dari cPanel.'),
            ];
        } catch (Throwable) {
            return ['configured' => true, 'available' => false, 'message' => 'Koneksi statistik cPanel gagal. Periksa API URL, token, dan izin akun.'];
        }
    }

    private function databaseSize(): int
    {
        try {
            $driver = DB::connection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $row = DB::selectOne('SELECT COALESCE(SUM(data_length + index_length), 0) AS bytes FROM information_schema.tables WHERE table_schema = DATABASE()');
                return (int) ($row->bytes ?? 0);
            }
            if ($driver === 'sqlite') {
                $path = (string) config('database.connections.sqlite.database');
                if ($path !== '' && $path !== ':memory:') {
                    if (! str_starts_with($path, DIRECTORY_SEPARATOR)) $path = base_path($path);
                    return is_file($path) ? (int) filesize($path) : 0;
                }
            }
        } catch (Throwable) {
            return 0;
        }
        return 0;
    }

    private function remoteDiskSize(string $diskName, string $prefix): int
    {
        try {
            $disk = Storage::disk($diskName);
            $total = 0;
            foreach ($disk->allFiles($prefix) as $file) {
                $total += (int) $disk->size($file);
            }
            return $total;
        } catch (Throwable) {
            return 0;
        }
    }

    private function directorySize(string $path): int
    {
        if (! is_dir($path)) return 0;
        $total = 0;
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $item) {
                if ($item->isFile() && ! $item->isLink()) $total += $item->getSize();
            }
        } catch (Throwable) {
            return $total;
        }
        return $total;
    }

    private function parseQuantity(string $value): ?int
    {
        $value = trim(strtolower(str_replace(',', '.', $value)));
        if ($value === '' || $value === 'unlimited' || $value === '∞') return null;
        if (! preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*(b|bytes?|kb|kib|mb|mib|gb|gib|tb|tib)?$/i', $value, $matches)) return null;
        $number = (float) $matches[1];
        $unit = $matches[2] ?? 'b';
        $power = match ($unit) {
            'kb', 'kib' => 1,
            'mb', 'mib' => 2,
            'gb', 'gib' => 3,
            'tb', 'tib' => 4,
            default => 0,
        };
        return (int) round($number * (1024 ** $power));
    }
}
