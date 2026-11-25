<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Membaca kuota disk akun hosting melalui cPanel UAPI StatsBar::get_stats.
 * Token hanya digunakan di server; endpoint browser hanya menerima data statistik.
 */
class CpanelStorageService
{
    public function usage(bool $refresh = false): array
    {
        $baseUrl = rtrim((string) config('services.cpanel.base_url'), '/');
        $username = trim((string) config('services.cpanel.username'));
        $token = trim((string) config('services.cpanel.api_token'));

        if ($baseUrl === '' || $username === '' || $token === '') {
            return $this->unavailable(
                'Konfigurasi API cPanel belum lengkap. Periksa BACKUP_CPANEL_API_URL, BACKUP_CPANEL_USERNAME, dan BACKUP_CPANEL_API_TOKEN di .env.'
            );
        }

        // Hindari mengirim token ke alamat non-HTTPS atau URL yang tidak valid.
        $parts = parse_url($baseUrl);
        if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return $this->unavailable('Alamat API cPanel harus berupa URL HTTPS yang valid.');
        }

        $cacheKey = 'cpanel-storage-usage:' . hash('sha256', strtolower($username) . '|' . $baseUrl);
        if ($refresh) {
            Cache::forget($cacheKey);
        }

        $seconds = max(15, min(300, (int) config('services.cpanel.cache_seconds', 45)));

        return Cache::remember($cacheKey, now()->addSeconds($seconds), function () use ($baseUrl, $username, $token): array {
            return $this->fetch($baseUrl, $username, $token);
        });
    }

    private function fetch(string $baseUrl, string $username, string $token): array
    {
        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'Authorization' => 'cpanel ' . $username . ':' . $token,
                ])
                ->connectTimeout(5)
                ->timeout(max(5, min(30, (int) config('services.cpanel.timeout', 12))))
                ->get($baseUrl . '/execute/StatsBar/get_stats', [
                    'display' => 'diskusage',
                ]);

            if (! $response->successful()) {
                return $this->unavailable('cPanel menolak permintaan statistik. Periksa hostname, API Token, dan izin akses token.');
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                return $this->unavailable('Respons statistik cPanel tidak dapat dibaca sebagai JSON.');
            }

            $result = data_get($payload, 'result', []);
            if (! is_array($result) || (int) data_get($result, 'status', 0) !== 1) {
                return $this->unavailable('cPanel belum dapat memberikan statistik disk untuk akun ini.');
            }

            $rows = data_get($result, 'data', []);
            if (! is_array($rows)) {
                return $this->unavailable('Format statistik disk dari cPanel tidak dikenali.');
            }
            if (isset($rows['id']) || isset($rows['name'])) {
                $rows = [$rows];
            }

            $row = null;
            foreach ($rows as $candidate) {
                if (! is_array($candidate)) {
                    continue;
                }
                $id = strtolower((string) ($candidate['id'] ?? $candidate['name'] ?? ''));
                $item = strtolower((string) ($candidate['item'] ?? ''));
                if ($id === 'diskusage' || $id === 'disk_usage' || str_contains($item, 'disk space usage')) {
                    $row = $candidate;
                    break;
                }
            }

            if (! is_array($row)) {
                return $this->unavailable('Statistik Disk Space Usage tidak ditemukan pada respons cPanel.');
            }

            $usedRaw = $row['count'] ?? $row['_count'] ?? null;
            $quotaRaw = $row['max'] ?? $row['_max'] ?? null;
            $units = (string) ($row['units'] ?? 'MB');
            $usedBytes = $this->parseSize($usedRaw, $units);
            $quotaText = strtolower(trim((string) $quotaRaw));
            $isUnlimited = in_array($quotaText, ['unlimited', 'unlimited quota', '∞', 'infinity'], true)
                || (($quotaText === '0' || $quotaText === '0.0') && (bool) ($row['zeroisunlimited'] ?? false));
            $quotaBytes = $isUnlimited ? null : $this->parseSize($quotaRaw, $units);

            if ($usedBytes === null) {
                return $this->unavailable('Nilai pemakaian disk dari cPanel tidak memiliki satuan yang dikenali.');
            }

            $percent = null;
            if ($quotaBytes !== null && $quotaBytes > 0) {
                $percent = (int) round(($usedBytes / $quotaBytes) * 100);
                $percent = max(0, min(100, $percent));
            } elseif (isset($row['percent']) && is_numeric($row['percent']) && ! $isUnlimited) {
                $percent = max(0, min(100, (int) round((float) $row['percent'])));
            }

            $freeBytes = $quotaBytes !== null ? max(0, $quotaBytes - $usedBytes) : null;

            return [
                'available' => true,
                'source' => 'cPanel UAPI · StatsBar',
                'used_bytes' => $usedBytes,
                'used_display' => $this->formatBytes($usedBytes),
                'quota_bytes' => $quotaBytes,
                'quota_display' => $isUnlimited ? 'Tanpa batas' : ($quotaBytes !== null ? $this->formatBytes($quotaBytes) : 'Tidak tersedia'),
                'free_bytes' => $freeBytes,
                'free_display' => $isUnlimited ? 'Tanpa batas' : ($freeBytes !== null ? $this->formatBytes($freeBytes) : 'Tidak tersedia'),
                'is_unlimited' => $isUnlimited,
                'percent' => $percent,
                'percent_display' => $percent !== null ? $percent . '%' : '—',
                'checked_at' => now()->timezone('Asia/Jakarta')->toIso8601String(),
                'message' => null,
            ];
        } catch (Throwable $exception) {
            // Jangan log exception HTTP mentah karena dapat berisi detail koneksi sensitif.
            return $this->unavailable('Tidak dapat terhubung ke API cPanel. Periksa koneksi HTTPS dan konfigurasi hosting.');
        }
    }

    private function parseSize(mixed $value, string $fallbackUnit = 'MB'): ?int
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (is_numeric($text)) {
            $number = (float) $text;
            $unit = strtoupper(trim($fallbackUnit));
        } elseif (preg_match('/^([0-9]+(?:[.,][0-9]+)?)\s*([KMGTPE]?\s*(?:I?B|BYTES?))?$/i', $text, $matches)) {
            $number = (float) str_replace(',', '.', $matches[1]);
            $unit = strtoupper(str_replace(' ', '', $matches[2] ?? ''));
            if ($unit === '') {
                $unit = strtoupper(trim($fallbackUnit));
            }
        } else {
            return null;
        }

        $unit = str_replace(['BYTES', 'BYTE'], ['B', 'B'], $unit);
        $unit = str_replace('IB', 'B', $unit);
        $power = match ($unit) {
            'B', 'BYTE', 'BYTES' => 0,
            'KB', 'K' => 1,
            'MB', 'M' => 2,
            'GB', 'G' => 3,
            'TB', 'T' => 4,
            'PB', 'P' => 5,
            'EB', 'E' => 6,
            default => null,
        };

        if ($power === null) {
            return null;
        }

        return (int) round($number * (1024 ** $power));
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return number_format($bytes, 0, ',', '.') . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB', 'PB'];
        $value = (float) $bytes;
        foreach ($units as $unit) {
            $value /= 1024;
            if ($value < 1024 || $unit === 'PB') {
                return number_format($value, 2, ',', '.') . ' ' . $unit;
            }
        }

        return number_format($value, 2, ',', '.') . ' PB';
    }

    private function unavailable(string $message): array
    {
        return [
            'available' => false,
            'source' => 'cPanel UAPI · StatsBar',
            'used_bytes' => null,
            'used_display' => '—',
            'quota_bytes' => null,
            'quota_display' => '—',
            'free_bytes' => null,
            'free_display' => '—',
            'is_unlimited' => false,
            'percent' => null,
            'percent_display' => '—',
            'checked_at' => now()->timezone('Asia/Jakarta')->toIso8601String(),
            'message' => $message,
        ];
    }
}
