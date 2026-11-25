<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Phar;
use PharData;
use RuntimeException;

class BackupPackageService
{
    /**
     * Build a portable ZIP containing the already-encrypted .sawb snapshot.
     * The returned work directory must be removed with cleanup() after download/upload.
     */
    public function build(Backup $backup): array
    {
        if (! $backup->isComplete() || blank($backup->path) || blank($backup->checksum)) {
            throw new RuntimeException('Backup belum selesai atau tidak memiliki checksum.');
        }
        if (! class_exists(PharData::class) || ! extension_loaded('zlib')) {
            throw new RuntimeException('Ekstensi Phar dan zlib wajib aktif untuk membuat ZIP backup.');
        }

        $disk = Storage::disk($backup->disk);
        if (! $disk->exists($backup->path)) {
            throw new RuntimeException('File backup tidak ditemukan pada media penyimpanan.');
        }

        $workDir = storage_path('app/private/.backup-packages/'.Str::uuid());
        File::ensureDirectoryExists($workDir, 0700, true);
        $payloadPath = $workDir.'/'.$backup->filename;
        $zipPath = $workDir.'/'.pathinfo($backup->filename, PATHINFO_FILENAME).'.zip';

        try {
            $source = $disk->readStream($backup->path);
            $target = fopen($payloadPath, 'wb');
            if (! is_resource($source) || $target === false) {
                if (is_resource($source)) fclose($source);
                throw new RuntimeException('File backup tidak dapat disiapkan untuk ZIP.');
            }
            try {
                if (stream_copy_to_stream($source, $target) === false) {
                    throw new RuntimeException('File backup gagal disalin ke paket ZIP.');
                }
            } finally {
                fclose($source);
                fclose($target);
            }

            if (! hash_equals((string) $backup->checksum, (string) hash_file('sha256', $payloadPath))) {
                throw new RuntimeException('Checksum backup tidak cocok saat membuat paket ZIP.');
            }

            $archive = new PharData($zipPath, 0, null, Phar::ZIP);
            $archive->addFile($payloadPath, $backup->filename);
            $archive->addFromString('manifest.json', json_encode([
                'application' => 'SIMPAN-AWAS',
                'format' => 'SIMPANAWAS-PORTABLE-ZIP-V1',
                'created_at' => now()->toIso8601String(),
                'backup_filename' => $backup->filename,
                'backup_checksum_sha256' => $backup->checksum,
                'backup_size_bytes' => (int) $backup->size,
                'encryption' => 'AES-256-GCM (inner .sawb file)',
                'includes' => ['encrypted_snapshot'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $archive->addFromString('PETUNJUK-PEMULIHAN.txt', $this->recoveryInstructions($backup));
            unset($archive);

            if (! is_file($zipPath) || filesize($zipPath) < 100) {
                throw new RuntimeException('Paket ZIP gagal dibuat.');
            }

            return ['path' => $zipPath, 'work_dir' => $workDir, 'filename' => basename($zipPath)];
        } catch (\Throwable $exception) {
            File::deleteDirectory($workDir);
            throw $exception;
        }
    }

    /** Import a ZIP downloaded from SIMPAN-AWAS and register its encrypted .sawb file. */
    public function importZip(string $zipPath, BackupService $backupService, ?string $diskName = null): Backup
    {
        if (! is_file($zipPath) || ! is_readable($zipPath)) {
            throw new RuntimeException('File ZIP tidak ditemukan atau tidak dapat dibaca.');
        }
        $maxBytes = (int) config('backup.max_import_bytes', 2147483648);
        if (filesize($zipPath) > $maxBytes) {
            throw new RuntimeException('Ukuran ZIP melebihi batas impor yang dikonfigurasi.');
        }

        $workDir = storage_path('app/private/.backup-imports/'.Str::uuid());
        File::ensureDirectoryExists($workDir, 0700, true);
        try {
            $archive = new PharData($zipPath);
            if (! isset($archive['manifest.json'])) {
                throw new RuntimeException('Manifest paket backup tidak ditemukan.');
            }
            $manifest = json_decode($archive['manifest.json']->getContent(), true);
            if (! is_array($manifest) || ($manifest['format'] ?? null) !== 'SIMPANAWAS-PORTABLE-ZIP-V1') {
                throw new RuntimeException('Format paket ZIP bukan paket backup SIMPAN-AWAS.');
            }

            $payloads = [];
            foreach ($archive as $entry) {
                if ($entry->isDir()) continue;
                $name = $entry->getFilename();
                if (in_array($name, ['manifest.json', 'PETUNJUK-PEMULIHAN.txt'], true)) continue;
                if (! str_ends_with(strtolower($name), '.sawb') || $name !== basename($name)) {
                    throw new RuntimeException('ZIP berisi item yang tidak dikenal atau jalur yang tidak aman.');
                }
                if ((int) $entry->getSize() > $maxBytes) {
                    throw new RuntimeException('Ukuran file backup di dalam ZIP melebihi batas impor.');
                }
                $payloads[] = $entry;
            }
            if (count($payloads) !== 1) {
                throw new RuntimeException('ZIP harus berisi tepat satu file backup .sawb.');
            }

            $entry = $payloads[0];
            $filename = basename($entry->getFilename());
            if (($manifest['backup_filename'] ?? null) !== $filename) {
                throw new RuntimeException('Nama file backup tidak cocok dengan manifest.');
            }
            $temporaryPayload = $workDir.'/'.$filename;
            $source = fopen($entry->getPathname(), 'rb');
            $target = fopen($temporaryPayload, 'wb');
            if (! is_resource($source) || $target === false) {
                if (is_resource($source)) fclose($source);
                if (is_resource($target)) fclose($target);
                throw new RuntimeException('Payload backup di dalam ZIP tidak dapat disiapkan.');
            }
            try {
                if (stream_copy_to_stream($source, $target) === false) {
                    throw new RuntimeException('Payload backup gagal diekstrak.');
                }
            } finally {
                fclose($source);
                fclose($target);
            }

            $actualChecksum = hash_file('sha256', $temporaryPayload);
            if (! is_string($actualChecksum) || ! hash_equals((string) ($manifest['backup_checksum_sha256'] ?? ''), $actualChecksum)) {
                throw new RuntimeException('Checksum file di dalam ZIP tidak cocok dengan manifest.');
            }

            $diskName = $diskName ?: (string) config('backup.disk', 'local');
            $disk = Storage::disk($diskName);
            $targetPath = trim((string) config('backup.path', 'backups'), '/').'/'.$filename;
            if ($disk->exists($targetPath)) {
                $filename = pathinfo($filename, PATHINFO_FILENAME).'-'.Str::lower(Str::random(6)).'.sawb';
                $targetPath = trim((string) config('backup.path', 'backups'), '/').'/'.$filename;
            }

            $stream = fopen($temporaryPayload, 'rb');
            if (! is_resource($stream)) throw new RuntimeException('Payload hasil ekstraksi tidak dapat dibaca.');
            try {
                $stored = $disk->put($targetPath, $stream);
            } finally {
                fclose($stream);
            }
            if (! $stored) throw new RuntimeException('File backup gagal disalin ke storage aplikasi.');

            try {
                return $backupService->registerBackupFile($targetPath, $diskName);
            } catch (\Throwable $exception) {
                $disk->delete($targetPath);
                throw $exception;
            }
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new RuntimeException('ZIP tidak valid atau tidak dapat dibaca.', previous: $exception);
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    public function cleanup(array $package): void
    {
        $workDir = $package['work_dir'] ?? null;
        if (is_string($workDir) && str_starts_with($workDir, storage_path('app/private/.backup-packages/'))) {
            File::deleteDirectory($workDir);
        }
    }

    private function recoveryInstructions(Backup $backup): string
    {
        return "SIMPAN-AWAS — PAKET BACKUP TERENKRIPSI\n\n"
            ."Isi paket ini adalah satu file .sawb yang dienkripsi AES-256-GCM. Jangan mengganti nama file .sawb di dalam ZIP.\n\n"
            ."PERSYARATAN PEMULIHAN\n"
            ."- Source code SIMPAN-AWAS yang kompatibel.\n"
            ."- Database kosong/target dan storage hosting yang sudah disiapkan.\n"
            ."- Nilai BACKUP_ENCRYPTION_KEY yang sama seperti saat backup dibuat.\n"
            ."- PHP dengan ekstensi Phar, zlib, OpenSSL, serta binary mysql/mysqldump untuk MySQL.\n\n"
            ."LANGKAH RINGKAS\n"
            ."1. Upload ZIP ini ke server dan jalankan: php artisan backup:import-zip /path/ke/file.zip\n"
            ."2. Perintah tersebut mendaftarkan file .sawb ke disk backup yang dikonfigurasi.\n"
            ."3. Pulihkan melalui menu Backup & Pemulihan atau perintah backup:restore-file.\n"
            ."4. Selalu buat salinan kondisi server saat ini sebelum restore.\n\n"
            ."Nama snapshot: {$backup->filename}\n"
            ."SHA-256: {$backup->checksum}\n"
            ."Catatan: ZIP ini tidak berisi .env, APP_KEY, BACKUP_ENCRYPTION_KEY, atau kredensial hosting.\n";
    }
}
