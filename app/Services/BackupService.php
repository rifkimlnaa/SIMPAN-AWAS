<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Phar;
use PharData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class BackupService
{
    private const FORMAT = 'SIMPANAWAS-BACKUP-V1';
    private const MAGIC = 'SAWB1';

    public function create(string $type = 'manual', ?int $userId = null): Backup
    {
        if (! in_array($type, ['manual', 'daily', 'weekly', 'monthly', 'pre-restore'], true)) {
            throw new RuntimeException('Jenis backup tidak dikenal.');
        }

        $this->assertRuntimeRequirements();
        $key = $this->encryptionKey();
        $diskName = (string) config('backup.disk', 'local');
        $disk = Storage::disk($diskName);
        $id = (string) Str::uuid();
        $filename = 'simpan-awas-'.$type.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.sawb';
        $relativePath = trim((string) config('backup.path', 'backups'), '/').'/'.$filename;
        $workDir = storage_path('app/private/.backup-work/'.$id);
        File::ensureDirectoryExists($workDir, 0700, true);

        $record = Backup::create([
            'type' => $type,
            'status' => 'running',
            'disk' => $diskName,
            'path' => $relativePath,
            'filename' => $filename,
            'created_by' => $userId,
            'metadata' => ['format' => self::FORMAT],
        ]);

        try {
            $databaseInfo = $this->dumpDatabase($workDir);
            $tarPath = $workDir.'/snapshot.tar';
            $fileCount = $this->buildTarArchive($tarPath, $workDir, $databaseInfo);

            $tarGzPath = $tarPath.'.gz';
            if (! is_file($tarGzPath)) {
                throw new RuntimeException('Arsip snapshot gagal dikompresi.');
            }

            $encryptedPath = $workDir.'/'.$filename;
            $this->encryptFile($tarGzPath, $encryptedPath, $key);

            $stream = fopen($encryptedPath, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Berkas backup terenkripsi tidak dapat dibaca.');
            }

            try {
                $stored = $disk->put($relativePath, $stream);
            } finally {
                fclose($stream);
            }

            if (! $stored) {
                throw new RuntimeException('Penyimpanan backup gagal. Periksa kapasitas dan konfigurasi disk.');
            }

            $record->update([
                'status' => 'complete',
                'size' => (int) filesize($encryptedPath),
                'checksum' => hash_file('sha256', $encryptedPath),
                'completed_at' => now(),
                'metadata' => array_merge($record->metadata ?? [], [
                    'database_driver' => $databaseInfo['driver'],
                    'file_count' => $fileCount,
                    'encrypted' => true,
                    'format' => self::FORMAT,
                ]),
            ]);

            return $record->fresh();
        } catch (Throwable $exception) {
            try {
                $disk->delete($relativePath);
            } catch (Throwable $cleanupException) {
                Log::warning('Gagal membersihkan file backup parsial.', [
                    'backup_id' => $record->id,
                    'exception' => $cleanupException,
                ]);
            }

            Log::error('SIMPAN-AWAS backup failed', [
                'backup_id' => $record->id,
                'type' => $type,
                'exception' => $exception,
            ]);

            $record->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 490),
                'completed_at' => now(),
            ]);

            throw new RuntimeException('Backup gagal: '.$exception->getMessage(), previous: $exception);
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    /** Restore only an existing, checksum-verified backup created by this application. */
    public function restore(Backup $backup, ?int $userId): Backup
    {
        if (! $backup->isComplete() || blank($backup->path) || blank($backup->checksum)) {
            throw new RuntimeException('Backup belum selesai atau tidak memiliki checksum.');
        }

        $this->assertRuntimeRequirements();
        $key = $this->encryptionKey();
        $workDir = storage_path('app/private/.restore-work/'.Str::uuid());
        File::ensureDirectoryExists($workDir, 0700, true);

        // Snapshot kondisi saat ini disimpan terlebih dahulu sebagai titik kembali.
        $safetyBackup = $this->create('pre-restore', $userId);

        try {
            $encryptedPath = $workDir.'/selected.sawb';
            $this->copyFromDisk($backup, $encryptedPath);

            $actualChecksum = hash_file('sha256', $encryptedPath);
            if (! hash_equals((string) $backup->checksum, (string) $actualChecksum)) {
                throw new RuntimeException('Checksum backup tidak cocok. Pemulihan dibatalkan.');
            }

            $tarGzPath = $workDir.'/snapshot.tar.gz';
            $this->decryptFile($encryptedPath, $tarGzPath, $key);
            $extractDir = $workDir.'/extract';
            File::ensureDirectoryExists($extractDir, 0700, true);

            $archive = new PharData($tarGzPath);
            $this->validateArchive($archive);
            $archive->extractTo($extractDir, null, true);
            unset($archive);

            $manifestPath = $extractDir.'/manifest.json';
            if (! is_file($manifestPath)) {
                throw new RuntimeException('Manifest backup tidak ditemukan.');
            }
            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            if (! is_array($manifest) || ($manifest['format'] ?? null) !== self::FORMAT) {
                throw new RuntimeException('Format backup tidak dikenali.');
            }
            $requiredIncludes = ['database', 'storage/private/arsip', 'storage/public'];
            if (array_diff($requiredIncludes, (array) ($manifest['includes'] ?? [])) !== []) {
                throw new RuntimeException('Backup tidak mencakup seluruh data yang diwajibkan.');
            }

            $databaseDriver = (string) ($manifest['database_driver'] ?? '');
            $currentConnection = (array) config('database.connections.'.config('database.default'), []);
            $currentDriver = (string) ($currentConnection['driver'] ?? config('database.default'));
            if ($databaseDriver !== $currentDriver) {
                throw new RuntimeException('Driver database backup berbeda dengan database aplikasi saat ini.');
            }

            $this->restoreDatabase($extractDir, $databaseDriver);
            $this->restoreStorageDirectories($extractDir);

            // Restore database lama dapat mengembalikan tabel backups ke keadaan snapshot.
            // Daftarkan kembali backup target dan safety snapshot bila barisnya tidak ada.
            $targetRecord = $this->ensureBackupRecordExists($backup);
            $safetyRecord = $this->ensureBackupRecordExists($safetyBackup);

            $targetRecord->update(['restored_by' => $userId, 'restored_at' => now()]);

            return $safetyRecord->fresh();
        } catch (Throwable $exception) {
            try {
                // Keep the safety snapshot visible even if the previous DB snapshot overwrote backup metadata.
                $this->ensureBackupRecordExists($safetyBackup);
            } catch (Throwable $recordException) {
                Log::critical('Safety backup metadata could not be re-registered after a failed restore.', [
                    'safety_backup_id' => $safetyBackup->id,
                    'exception' => $recordException,
                ]);
            }

            Log::error('SIMPAN-AWAS restore failed', [
                'backup_id' => $backup->id,
                'safety_backup_id' => $safetyBackup->id,
                'exception' => $exception,
            ]);

            throw new RuntimeException(
                'Pemulihan gagal. Backup pengaman sebelum pemulihan tetap disimpan sebagai '.$safetyBackup->filename.'. Detail: '.$exception->getMessage(),
                previous: $exception
            );
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    /** Register a previously copied .sawb file after a server/database disaster. */
    public function registerBackupFile(string $path, ?string $diskName = null): Backup
    {
        $path = trim($path, '/');
        if ($path === '' || str_contains($path, '..') || str_contains($path, '\\') || str_starts_with($path, '.')) {
            throw new RuntimeException('Path backup tidak valid.');
        }

        $diskName = $diskName ?: (string) config('backup.disk', 'local');
        if (! config('filesystems.disks.'.$diskName)) {
            throw new RuntimeException('Disk penyimpanan backup belum dikonfigurasi.');
        }
        $disk = Storage::disk($diskName);
        if (! $disk->exists($path)) {
            throw new RuntimeException('File backup tidak ditemukan pada disk yang dipilih.');
        }

        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            throw new RuntimeException('File backup tidak dapat dibaca.');
        }
        try {
            $magic = fread($stream, 5);
            if ($magic !== self::MAGIC) {
                throw new RuntimeException('File bukan backup SIMPAN-AWAS terenkripsi (.sawb).');
            }
            $context = hash_init('sha256');
            hash_update($context, $magic);
            hash_update_stream($context, $stream);
            $checksum = hash_final($context);
        } finally {
            fclose($stream);
        }

        $filename = basename($path);
        if (! str_ends_with(strtolower($filename), '.sawb')) {
            throw new RuntimeException('Ekstensi file backup harus .sawb.');
        }

        return Backup::query()->updateOrCreate(
            ['disk' => $diskName, 'path' => $path],
            [
                'type' => 'manual',
                'status' => 'complete',
                'filename' => $filename,
                'size' => (int) $disk->size($path),
                'checksum' => $checksum,
                'completed_at' => now(),
                'error_message' => null,
                'metadata' => ['format' => self::FORMAT, 'registered_from_file' => true],
            ]
        );
    }

    public function delete(Backup $backup): void
    {
        if ($backup->path) {
            $disk = Storage::disk($backup->disk);
            $disk->delete($backup->path);
            if ($disk->exists($backup->path)) {
                throw new RuntimeException('File backup belum berhasil dihapus dari penyimpanan.');
            }
        }
        $backup->delete();
    }

    public function cleanup(): int
    {
        $retention = (array) config('backup.retention', []);
        $rules = [
            'daily' => now()->subDays(max(1, (int) ($retention['daily_days'] ?? 7))),
            'weekly' => now()->subWeeks(max(1, (int) ($retention['weekly_weeks'] ?? 4))),
            'monthly' => now()->subMonths(max(1, (int) ($retention['monthly_months'] ?? 12))),
            'pre-restore' => now()->subDays(max(1, (int) ($retention['pre_restore_days'] ?? 14))),
        ];

        $deleted = 0;
        foreach ($rules as $type => $cutoff) {
            $old = Backup::query()
                ->where('type', $type)
                ->where('status', 'complete')
                ->where('created_at', '<', $cutoff)
                ->get();

            foreach ($old as $backup) {
                $this->delete($backup);
                $deleted++;
            }
        }

        // Bersihkan sisa direktori sementara bila proses PHP sebelumnya terhenti mendadak.
        foreach ([storage_path('app/private/.backup-work'), storage_path('app/private/.restore-work')] as $workRoot) {
            if (! is_dir($workRoot)) {
                continue;
            }
            foreach (glob($workRoot.'/*', GLOB_ONLYDIR) ?: [] as $directory) {
                if (filemtime($directory) !== false && filemtime($directory) < now()->subDay()->timestamp) {
                    File::deleteDirectory($directory);
                }
            }
        }

        // Catatan/file kegagalan yang terlalu lama tidak perlu memenuhi riwayat.
        $failedBackups = Backup::query()->where('status', 'failed')->where('created_at', '<', now()->subDays(30))->get();
        foreach ($failedBackups as $failedBackup) {
            $this->delete($failedBackup);
            $deleted++;
        }

        return $deleted;
    }

    private function dumpDatabase(string $workDir): array
    {
        $connectionName = (string) config('database.default');
        $connection = (array) config('database.connections.'.$connectionName, []);
        $driver = (string) ($connection['driver'] ?? $connectionName);

        if ($driver === 'sqlite') {
            $databasePath = (string) ($connection['database'] ?? '');
            if ($databasePath === '' || $databasePath === ':memory:') {
                throw new RuntimeException('Database SQLite in-memory tidak dapat dicadangkan.');
            }
            if (! str_starts_with($databasePath, DIRECTORY_SEPARATOR)) {
                $databasePath = base_path($databasePath);
            }
            if (! is_file($databasePath)) {
                throw new RuntimeException('File database SQLite tidak ditemukan.');
            }

            $target = $workDir.'/database.sqlite';
            if (! copy($databasePath, $target)) {
                throw new RuntimeException('Database SQLite gagal disalin.');
            }

            return ['driver' => 'sqlite', 'database_file' => $target, 'file_count' => 1];
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Backup saat ini mendukung database MySQL/MariaDB dan SQLite.');
        }

        $database = (string) ($connection['database'] ?? '');
        if ($database === '') {
            throw new RuntimeException('Nama database belum dikonfigurasi.');
        }

        $command = [
            (string) config('backup.mysql_dump_binary', 'mysqldump'),
            '--single-transaction', '--quick', '--routines', '--triggers', '--hex-blob',
            '--default-character-set=utf8mb4',
        ];

        if (! empty($connection['socket'])) {
            $command[] = '--socket='.(string) $connection['socket'];
        } else {
            $command[] = '--host='.(string) ($connection['host'] ?? '127.0.0.1');
            $command[] = '--port='.(string) ($connection['port'] ?? 3306);
        }
        if (! empty($connection['username'])) {
            $command[] = '--user='.(string) $connection['username'];
        }
        $command[] = $database;

        $environment = [];
        if (isset($connection['password']) && $connection['password'] !== '') {
            // Password tidak diletakkan di argumen command line.
            $environment['MYSQL_PWD'] = (string) $connection['password'];
        }

        $target = $workDir.'/database.sql';
        $handle = fopen($target, 'wb');
        if ($handle === false) {
            throw new RuntimeException('File dump database tidak dapat dibuat.');
        }

        try {
            $process = new Process($command, base_path(), $environment, null, (int) config('backup.process_timeout', 1800));
            $process->run(function (string $type, string $buffer) use ($handle): void {
                if ($type === Process::OUT && fwrite($handle, $buffer) === false) {
                    throw new RuntimeException('Tidak dapat menulis hasil dump database.');
                }
            });
            if (! $process->isSuccessful()) {
                throw new RuntimeException('mysqldump gagal dijalankan. Periksa apakah binary tersedia dan kredensial database benar.');
            }
        } finally {
            fclose($handle);
        }

        if (! is_file($target) || filesize($target) < 20) {
            throw new RuntimeException('Hasil dump database kosong atau tidak lengkap.');
        }

        return ['driver' => $driver, 'database_file' => $target, 'file_count' => 1];
    }

    private function buildTarArchive(string $tarPath, string $workDir, array $databaseInfo): int
    {
        $archive = new PharData($tarPath);
        $databaseArchivePath = $databaseInfo['driver'] === 'sqlite' ? 'database/database.sqlite' : 'database/database.sql';
        $archive->addFile($databaseInfo['database_file'], $databaseArchivePath);

        $privateRoot = (string) config('filesystems.disks.local.root', storage_path('app/private'));
        $publicRoot = (string) config('filesystems.disks.public.root', storage_path('app/public'));
        $fileCount = 1;

        $this->addDirectory($archive, rtrim($privateRoot, '/\\').'/arsip', 'storage/private/arsip', $fileCount);
        $this->addDirectory($archive, rtrim($publicRoot, '/\\'), 'storage/public', $fileCount);

        $manifest = [
            'format' => self::FORMAT,
            'created_at' => now()->toIso8601String(),
            'app_name' => config('app.name'),
            'database_driver' => $databaseInfo['driver'],
            'file_count' => $fileCount,
            'includes' => ['database', 'storage/private/arsip', 'storage/public'],
        ];
        $archive->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        unset($archive);

        return $fileCount;
    }

    private function addDirectory(PharData $archive, string $source, string $archivePrefix, int &$fileCount): void
    {
        if (! is_dir($source)) {
            $archive->addEmptyDir($archivePrefix);
            return;
        }

        $archive->addEmptyDir($archivePrefix);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $fullPath = $item->getPathname();
            if ($item->isLink()) {
                continue;
            }

            $relative = ltrim(substr($fullPath, strlen(rtrim($source, '/\\'))), '/\\');
            if ($relative === '' || str_contains($relative, "\0")) {
                continue;
            }
            $localName = $archivePrefix.'/'.$relative;

            if ($item->isDir()) {
                $archive->addEmptyDir($localName);
            } elseif ($item->isFile()) {
                $archive->addFile($fullPath, $localName);
                $fileCount++;
            }
        }
    }

    private function encryptFile(string $source, string $destination, string $secret): void
    {
        $input = fopen($source, 'rb');
        $output = fopen($destination, 'wb');
        if (! $input || ! $output) {
            throw new RuntimeException('File sementara backup tidak dapat dibuka.');
        }

        $salt = random_bytes(16);
        $noncePrefix = random_bytes(8);
        $key = hash_pbkdf2('sha256', $secret, $salt, 310000, 32, true);
        $header = self::MAGIC.$salt.$noncePrefix;
        fwrite($output, $header);
        $counter = 0;

        try {
            while (! feof($input)) {
                $plain = fread($input, (int) config('backup.chunk_size', 1048576));
                if ($plain === false) {
                    throw new RuntimeException('Gagal membaca snapshot untuk dienkripsi.');
                }
                if ($plain === '') {
                    continue;
                }
                if ($counter > 0xFFFFFFFF) {
                    throw new RuntimeException('Ukuran backup melebihi kapasitas format enkripsi.');
                }

                $counterBytes = pack('N', $counter);
                $nonce = $noncePrefix.$counterBytes;
                $tag = '';
                $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, $header.$counterBytes, 16);
                if ($cipher === false || strlen($tag) !== 16) {
                    throw new RuntimeException('Enkripsi snapshot gagal.');
                }
                $this->writeAll($output, pack('N', strlen($cipher)).$cipher.$tag);
                $counter++;
            }

            // Authenticated end marker: a truncated file cannot masquerade as a complete backup.
            $endCounter = pack('N', $counter);
            $endTag = '';
            $endCipher = openssl_encrypt('', 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $noncePrefix.$endCounter, $endTag, $header.$endCounter.'END', 16);
            if ($endCipher === false || strlen($endTag) !== 16) {
                throw new RuntimeException('Penanda akhir enkripsi gagal dibuat.');
            }
            $this->writeAll($output, pack('N', 0).$endTag);
        } finally {
            fclose($input);
            fclose($output);
        }
    }

    private function decryptFile(string $source, string $destination, string $secret): void
    {
        $input = fopen($source, 'rb');
        $output = fopen($destination, 'wb');
        if (! $input || ! $output) {
            throw new RuntimeException('File backup terenkripsi tidak dapat dibuka.');
        }

        try {
            $magic = $this->readExact($input, 5);
            if ($magic !== self::MAGIC) {
                throw new RuntimeException('Format file backup tidak dikenali.');
            }
            $salt = $this->readExact($input, 16);
            $noncePrefix = $this->readExact($input, 8);
            $header = $magic.$salt.$noncePrefix;
            $key = hash_pbkdf2('sha256', $secret, $salt, 310000, 32, true);
            $counter = 0;
            $maxChunk = (int) config('backup.chunk_size', 1048576);
            $ended = false;

            while (! feof($input)) {
                $lengthBytes = fread($input, 4);
                if ($lengthBytes === '' || $lengthBytes === false) {
                    break;
                }
                if (strlen($lengthBytes) !== 4) {
                    throw new RuntimeException('Backup terenkripsi terpotong.');
                }
                $length = unpack('Nlength', $lengthBytes)['length'] ?? 0;
                $counterBytes = pack('N', $counter);

                if ($length === 0) {
                    $tag = $this->readExact($input, 16);
                    $end = openssl_decrypt('', 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $noncePrefix.$counterBytes, $tag, $header.$counterBytes.'END');
                    $extraByte = fread($input, 1);
                    if ($end === false || $counter === 0 || $extraByte !== '') {
                        throw new RuntimeException('Penanda akhir backup tidak valid atau terdapat data tambahan.');
                    }
                    $ended = true;
                    break;
                }

                if ($length < 1 || $length > $maxChunk) {
                    throw new RuntimeException('Ukuran blok backup tidak valid.');
                }

                $cipher = $this->readExact($input, $length);
                $tag = $this->readExact($input, 16);
                $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $noncePrefix.$counterBytes, $tag, $header.$counterBytes);
                if ($plain === false) {
                    throw new RuntimeException('Autentikasi enkripsi gagal. Kunci backup salah atau file berubah.');
                }
                $this->writeAll($output, $plain);
                $counter++;
            }

            if ($counter === 0 || ! $ended) {
                throw new RuntimeException('Backup tidak lengkap atau tidak memiliki penanda akhir autentik.');
            }
        } finally {
            fclose($input);
            fclose($output);
        }
    }

    private function validateArchive(PharData $archive): void
    {
        $base = 'phar://'.rtrim($archive->getPath(), '/\\').'/';
        $iterator = new RecursiveIteratorIterator($archive, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $entryPath => $entry) {
            $entryPath = (string) $entryPath;
            if (! str_starts_with($entryPath, $base)) {
                throw new RuntimeException('Jalur internal arsip tidak valid.');
            }
            $relative = str_replace('\\', '/', substr($entryPath, strlen($base)));
            if ($relative === '' || str_starts_with($relative, '/') || preg_match('#(^|/)\.\.?(/|$)#', $relative) || $entry->isLink()) {
                throw new RuntimeException('Arsip berisi jalur atau tautan file yang tidak aman.');
            }

            $allowed = in_array($relative, [
                'manifest.json', 'database', 'storage', 'storage/private',
                'storage/private/arsip', 'storage/public',
            ], true)
                || str_starts_with($relative, 'database/')
                || str_starts_with($relative, 'storage/private/arsip/')
                || str_starts_with($relative, 'storage/public/');
            if (! $allowed) {
                throw new RuntimeException('Arsip berisi berkas di luar struktur backup SIMPAN-AWAS.');
            }
        }
    }

    private function restoreDatabase(string $extractDir, string $driver): void
    {
        if ($driver === 'sqlite') {
            $source = $extractDir.'/database/database.sqlite';
            if (! is_file($source)) {
                throw new RuntimeException('File database SQLite tidak ditemukan di backup.');
            }
            $connection = (array) config('database.connections.sqlite', []);
            $destination = (string) ($connection['database'] ?? '');
            if ($destination === '' || $destination === ':memory:') {
                throw new RuntimeException('Target SQLite tidak dapat dipulihkan.');
            }
            if (! str_starts_with($destination, DIRECTORY_SEPARATOR)) {
                $destination = base_path($destination);
            }
            File::ensureDirectoryExists(dirname($destination));
            if (! copy($source, $destination)) {
                throw new RuntimeException('Database SQLite gagal dipulihkan.');
            }

            return;
        }

        $source = $extractDir.'/database/database.sql';
        if (! is_file($source)) {
            throw new RuntimeException('Dump database tidak ditemukan di dalam backup.');
        }

        $connection = (array) config('database.connections.'.config('database.default'), []);
        $database = (string) ($connection['database'] ?? '');
        if ($database === '') {
            throw new RuntimeException('Database target belum dikonfigurasi.');
        }

        $command = [
            (string) config('backup.mysql_binary', 'mysql'),
            '--default-character-set=utf8mb4',
        ];
        if (! empty($connection['socket'])) {
            $command[] = '--socket='.(string) $connection['socket'];
        } else {
            $command[] = '--host='.(string) ($connection['host'] ?? '127.0.0.1');
            $command[] = '--port='.(string) ($connection['port'] ?? 3306);
        }
        if (! empty($connection['username'])) {
            $command[] = '--user='.(string) $connection['username'];
        }
        $command[] = $database;

        $environment = [];
        if (isset($connection['password']) && $connection['password'] !== '') {
            $environment['MYSQL_PWD'] = (string) $connection['password'];
        }
        $input = fopen($source, 'rb');
        if ($input === false) {
            throw new RuntimeException('Dump database tidak dapat dibaca.');
        }

        try {
            $process = new Process($command, base_path(), $environment, $input, (int) config('backup.process_timeout', 1800));
            $process->run();
            if (! $process->isSuccessful()) {
                throw new RuntimeException('Pemulihan database gagal. Periksa binary mysql dan hak akses database.');
            }
        } finally {
            fclose($input);
        }
    }

    private function restoreStorageDirectories(string $extractDir): void
    {
        $privateRoot = rtrim((string) config('filesystems.disks.local.root', storage_path('app/private')), '/\\');
        $publicRoot = rtrim((string) config('filesystems.disks.public.root', storage_path('app/public')), '/\\');
        $token = Str::random(10);
        $moves = [
            [$extractDir.'/storage/private/arsip', $privateRoot.'/arsip'],
            [$extractDir.'/storage/public', $publicRoot],
        ];
        $prepared = [];

        // Prepare both replacements before changing either active directory.
        foreach ($moves as [$source, $destination]) {
            if (! is_dir($source)) {
                throw new RuntimeException('Folder storage wajib tidak ditemukan di backup.');
            }
            File::ensureDirectoryExists(dirname($destination));
            $staged = $destination.'.restore-new-'.$token;
            $old = $destination.'.restore-old-'.$token;
            File::deleteDirectory($staged);
            if (! File::copyDirectory($source, $staged)) {
                foreach ($prepared as $item) {
                    File::deleteDirectory($item['staged']);
                }
                throw new RuntimeException('File backup gagal disiapkan untuk pemulihan.');
            }
            $prepared[] = ['destination' => $destination, 'staged' => $staged, 'old' => $old, 'had_old' => is_dir($destination)];
        }

        try {
            foreach ($prepared as $item) {
                if ($item['had_old'] && ! rename($item['destination'], $item['old'])) {
                    throw new RuntimeException('Folder aktif tidak dapat diamankan sebelum pemulihan.');
                }
            }
            foreach ($prepared as $item) {
                if (! rename($item['staged'], $item['destination'])) {
                    throw new RuntimeException('Folder hasil backup tidak dapat diaktifkan.');
                }
            }
        } catch (Throwable $exception) {
            foreach (array_reverse($prepared) as $item) {
                if (is_dir($item['old'])) {
                    if (is_dir($item['destination'])) {
                        File::deleteDirectory($item['destination']);
                    }
                    rename($item['old'], $item['destination']);
                } elseif (! $item['had_old'] && ! is_dir($item['staged']) && is_dir($item['destination'])) {
                    // The new directory was activated but there was no old directory to roll back to.
                    File::deleteDirectory($item['destination']);
                }
                File::deleteDirectory($item['staged']);
            }
            throw $exception;
        }

        foreach ($prepared as $item) {
            if (is_dir($item['old'])) {
                File::deleteDirectory($item['old']);
            }
        }
    }

    private function copyFromDisk(Backup $backup, string $destination): void
    {
        $stream = Storage::disk($backup->disk)->readStream($backup->path);
        if (! is_resource($stream)) {
            throw new RuntimeException('File backup tidak ditemukan pada penyimpanan.');
        }
        $target = fopen($destination, 'wb');
        if (! $target) {
            fclose($stream);
            throw new RuntimeException('File sementara restore tidak dapat dibuat.');
        }
        try {
            stream_copy_to_stream($stream, $target);
        } finally {
            fclose($stream);
            fclose($target);
        }
    }

    private function ensureBackupRecordExists(Backup $backup): Backup
    {
        $attributes = $backup->only([
            'type', 'status', 'disk', 'path', 'filename', 'size', 'checksum', 'created_by',
            'restored_by', 'restored_at', 'completed_at', 'error_message', 'metadata',
        ]);

        // The selected backup's own database snapshot may have captured its row while status=running.
        // Restore the original completed metadata before recording the restore event.
        return Backup::query()->updateOrCreate(
            ['disk' => $backup->disk, 'path' => $backup->path],
            collect($attributes)->except(['disk', 'path'])->all()
        );
    }

    private function assertRuntimeRequirements(): void
    {
        if (! class_exists(PharData::class) || ! extension_loaded('zlib')) {
            throw new RuntimeException('Ekstensi PHP Phar dan zlib wajib aktif pada hosting.');
        }
        if (! function_exists('openssl_encrypt') || ! function_exists('openssl_decrypt')) {
            throw new RuntimeException('Ekstensi PHP OpenSSL wajib aktif pada hosting.');
        }
    }

    private function encryptionKey(): string
    {
        $key = (string) config('backup.encryption_key', '');
        if (strlen($key) < 32) {
            throw new RuntimeException('BACKUP_ENCRYPTION_KEY belum diatur. Buat kunci acak minimal 32 karakter dan simpan di .env.');
        }

        return $key;
    }

    private function readExact($handle, int $length): string
    {
        $buffer = '';
        while (strlen($buffer) < $length && ! feof($handle)) {
            $part = fread($handle, $length - strlen($buffer));
            if ($part === false || $part === '') {
                break;
            }
            $buffer .= $part;
        }
        if (strlen($buffer) !== $length) {
            throw new RuntimeException('File backup terpotong atau rusak.');
        }

        return $buffer;
    }

    private function writeAll($handle, string $data): void
    {
        $offset = 0;
        $length = strlen($data);
        while ($offset < $length) {
            $written = fwrite($handle, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Gagal menulis file backup.');
            }
            $offset += $written;
        }
    }
}
