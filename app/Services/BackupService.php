<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PharData;
use PDO;
use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

class BackupService
{
    private const FORMAT = 'SIMPANAWAS-BACKUP-V2';
    private const LEGACY_FORMAT = 'SIMPANAWAS-BACKUP-V1';
    private const MAGIC = 'SAWB2';
    private const LEGACY_MAGIC = 'SAWB1';

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
            'metadata' => ['format' => self::FORMAT, 'engine' => 'php-native'],
        ]);

        try {
            $databaseInfo = $this->dumpDatabase($workDir);
            $zipPath = $workDir.'/snapshot.zip';
            $fileCount = $this->buildZipArchive($zipPath, $databaseInfo);

            $encryptedPath = $workDir.'/'.$filename;
            $this->encryptFile($zipPath, $encryptedPath, $key);

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
                    'database_tables' => $databaseInfo['tables'] ?? [],
                    'file_count' => $fileCount,
                    'encrypted' => true,
                    'format' => self::FORMAT,
                    'engine' => 'php-native',
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

    /**
     * Backup sekali klik: database + seluruh berkas arsip dalam SATU file ZIP biasa (tanpa enkripsi),
     * langsung diunduh admin. Mengembalikan path ZIP; hapus setelah dikirim (deleteFileAfterSend).
     * Tidak dicatat di riwayat backup dan tidak bisa dipulihkan dari menu; ZIP ini untuk disimpan sendiri.
     */
    public function createDownloadZip(): string
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $workDir = storage_path('app/private/.backup-work/'.Str::uuid());
        File::ensureDirectoryExists($workDir, 0700, true);
        app()->terminating(fn () => File::deleteDirectory($workDir));

        $databaseInfo = $this->dumpDatabase($workDir);
        $zipPath = $workDir.'/simpan-awas-backup-'.now()->format('Ymd-His').'.zip';
        $this->buildZipArchive($zipPath, $databaseInfo);

        return $zipPath;
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

        // Simpan kondisi terkini sebelum pemulihan sebagai titik kembali.
        $safetyBackup = $this->create('pre-restore', $userId);

        try {
            $encryptedPath = $workDir.'/selected.sawb';
            $this->copyFromDisk($backup, $encryptedPath);

            $actualChecksum = hash_file('sha256', $encryptedPath);
            if (! hash_equals((string) $backup->checksum, (string) $actualChecksum)) {
                throw new RuntimeException('Checksum backup tidak cocok. Pemulihan dibatalkan.');
            }

            $payloadPath = $workDir.'/snapshot.payload';
            $magic = $this->decryptFile($encryptedPath, $payloadPath, $key);
            $extractDir = $workDir.'/extract';
            File::ensureDirectoryExists($extractDir, 0700, true);

            if ($magic === self::MAGIC) {
                $this->extractZipSnapshot($payloadPath, $extractDir);
            } elseif ($magic === self::LEGACY_MAGIC) {
                if (! class_exists(PharData::class)) {
                    throw new RuntimeException('Backup lama memakai format TAR.GZ dan memerlukan ekstensi Phar. Backup baru tidak lagi memerlukan Phar.');
                }
                $archive = new PharData($payloadPath);
                $this->validateArchive($archive);
                $archive->extractTo($extractDir, null, true);
                unset($archive);
            } else {
                throw new RuntimeException('Versi format backup tidak didukung.');
            }

            $manifestPath = $extractDir.'/manifest.json';
            if (! is_file($manifestPath)) {
                throw new RuntimeException('Manifest backup tidak ditemukan.');
            }
            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            $allowedFormats = [self::FORMAT, self::LEGACY_FORMAT];
            if (! is_array($manifest) || ! in_array(($manifest['format'] ?? null), $allowedFormats, true)) {
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

            $this->restoreDatabase($extractDir, $databaseDriver, (array) ($manifest['database_tables'] ?? []));
            $this->restoreStorageDirectories($extractDir);

            $targetRecord = $this->ensureBackupRecordExists($backup);
            $safetyRecord = $this->ensureBackupRecordExists($safetyBackup);
            $targetRecord->update(['restored_by' => $userId, 'restored_at' => now()]);

            return $safetyRecord->fresh();
        } catch (Throwable $exception) {
            try {
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
            if (! in_array($magic, [self::MAGIC, self::LEGACY_MAGIC], true)) {
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

    /** Export MySQL tables using PDO; this path does not call shell functions. */
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
                throw new RuntimeException('File database SQLite gagal disalin.');
            }
            return ['driver' => 'sqlite', 'database_file' => $target, 'tables' => []];
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Backup mendukung database MySQL/MariaDB dan SQLite.');
        }

        $pdo = \Illuminate\Support\Facades\DB::connection($connectionName)->getPdo();
        $tableRows = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
        $tables = array_values(array_map(static fn ($row) => (string) ($row[0] ?? ''), $tableRows));
        $tables = array_values(array_filter($tables, static fn ($name) => $name !== ''));
        $tables = $this->orderTablesByForeignKeys($pdo, $tables);
        if ($tables === []) {
            throw new RuntimeException('Tidak ada tabel database yang dapat dicadangkan.');
        }

        // Jangan membuat backup yang diam-diam mengabaikan objek DB non-tabel.
        try {
            $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_NUM);
            if ($views !== []) {
                throw new RuntimeException('Database memiliki VIEW. Ekspor VIEW perlu ditangani sebelum backup dapat dinyatakan lengkap.');
            }
            $triggerCount = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()')->fetchColumn();
            $routineCount = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE()")->fetchColumn();
            if ($triggerCount > 0 || $routineCount > 0) {
                throw new RuntimeException('Database memiliki trigger atau routine. Backup PHP murni saat ini hanya mengarsipkan tabel dan datanya; objek tersebut harus ditangani sebelum melanjutkan.');
            }
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // Hak akses terbatas ke information_schema tidak otomatis menggagalkan backup tabel.
            Log::notice('Tidak dapat memeriksa seluruh objek MySQL non-tabel saat backup.', ['message' => $exception->getMessage()]);
        }

        $target = $workDir.'/database.sql';
        $handle = fopen($target, 'wb');
        if ($handle === false) {
            throw new RuntimeException('File dump database tidak dapat dibuat.');
        }

        $transactionStarted = false;
        $bufferedAttribute = defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY') ? constant('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY') : null;
        $previousBuffered = null;

        try {
            $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $pdo->beginTransaction();
            $transactionStarted = true;

            $this->writeAll($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");

            foreach ($tables as $table) {
                $quotedTable = $this->quoteIdentifier($table);
                $createRow = $pdo->query('SHOW CREATE TABLE '.$quotedTable)->fetch(PDO::FETCH_NUM);
                $createSql = (string) ($createRow[1] ?? '');
                if ($createSql === '') {
                    throw new RuntimeException('Struktur tabel '.$table.' tidak dapat dibaca.');
                }

                $this->writeAll($handle, 'DROP TABLE IF EXISTS '.$quotedTable.";\n");
                $this->writeAll($handle, rtrim($createSql, "; \t\r\n").";\n");

                $columns = $pdo->query('SHOW FULL COLUMNS FROM '.$quotedTable)->fetchAll(PDO::FETCH_ASSOC);
                $insertColumns = [];
                $columnTypes = [];
                foreach ($columns as $column) {
                    $name = (string) ($column['Field'] ?? '');
                    $extra = strtolower((string) ($column['Extra'] ?? ''));
                    if ($name === '' || str_contains($extra, 'generated')) {
                        continue;
                    }
                    $insertColumns[] = $name;
                    $columnTypes[$name] = strtolower((string) ($column['Type'] ?? ''));
                }
                if ($insertColumns === []) {
                    continue;
                }

                if ($bufferedAttribute !== null) {
                    try {
                        $previousBuffered = $pdo->getAttribute($bufferedAttribute);
                        $pdo->setAttribute($bufferedAttribute, false);
                    } catch (Throwable) {
                        $previousBuffered = null;
                    }
                }

                $statement = $pdo->query('SELECT * FROM '.$quotedTable);
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $values = [];
                    foreach ($insertColumns as $columnName) {
                        $value = $row[$columnName] ?? null;
                        $values[] = $this->mysqlLiteral($pdo, $value, $columnTypes[$columnName] ?? '');
                    }
                    $columnSql = implode(', ', array_map(fn ($name) => $this->quoteIdentifier($name), $insertColumns));
                    $this->writeAll($handle, 'INSERT INTO '.$quotedTable.' ('.$columnSql.') VALUES ('.implode(', ', $values).');'."\n");
                }
                $statement->closeCursor();

                if ($bufferedAttribute !== null && $previousBuffered !== null) {
                    try { $pdo->setAttribute($bufferedAttribute, $previousBuffered); } catch (Throwable) {}
                    $previousBuffered = null;
                }
            }

            $this->writeAll($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            $pdo->commit();
            $transactionStarted = false;
        } catch (Throwable $exception) {
            if ($transactionStarted && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        } finally {
            if ($bufferedAttribute !== null && $previousBuffered !== null) {
                try { $pdo->setAttribute($bufferedAttribute, $previousBuffered); } catch (Throwable) {}
            }
            fclose($handle);
        }

        if (! is_file($target) || filesize($target) < 20) {
            throw new RuntimeException('Hasil ekspor database kosong atau tidak lengkap.');
        }

        return ['driver' => $driver, 'database_file' => $target, 'tables' => $tables];
    }


    /** Put referenced/parent tables before dependent tables for reliable CREATE TABLE restore order. */
    private function orderTablesByForeignKeys(PDO $pdo, array $tables): array
    {
        $known = array_fill_keys($tables, true);
        $dependencies = array_fill_keys($tables, []);
        try {
            $rows = $pdo->query('SELECT TABLE_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL')->fetchAll(PDO::FETCH_NUM);
            foreach ($rows as $row) {
                $table = (string) ($row[0] ?? '');
                $parent = (string) ($row[1] ?? '');
                if ($table !== '' && $parent !== '' && isset($known[$table]) && isset($known[$parent]) && $table !== $parent) {
                    $dependencies[$table][$parent] = true;
                }
            }
        } catch (Throwable $exception) {
            Log::notice('Tidak dapat membaca urutan foreign key; memakai urutan tabel dari MySQL.', ['message' => $exception->getMessage()]);
            return $tables;
        }

        $ordered = [];
        $remaining = array_fill_keys($tables, true);
        while ($remaining !== []) {
            $ready = [];
            foreach ($tables as $table) {
                if (! isset($remaining[$table])) continue;
                $waiting = false;
                foreach (array_keys($dependencies[$table]) as $parent) {
                    if (isset($remaining[$parent])) { $waiting = true; break; }
                }
                if (! $waiting) $ready[] = $table;
            }
            if ($ready === []) {
                throw new RuntimeException('Database memiliki relasi foreign key siklik. Urutan tabel tidak dapat ditentukan dengan aman untuk backup PHP-native.');
            }
            foreach ($ready as $table) {
                $ordered[] = $table;
                unset($remaining[$table]);
            }
        }
        return $ordered;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function mysqlLiteral(PDO $pdo, mixed $value, string $columnType): string
    {
        if ($value === null) return 'NULL';
        $type = strtolower($columnType);
        if (preg_match('/(tiny|medium|long)?blob|varbinary|\bbinary\b|\bbit\b/', $type)) {
            return '0x'.bin2hex((string) $value);
        }
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|numeric|float|double|real|year)/', $type)
            && is_numeric($value)) {
            return (string) $value;
        }
        $quoted = $pdo->quote((string) $value);
        if (! is_string($quoted)) {
            throw new RuntimeException('Nilai database tidak dapat diubah menjadi SQL yang aman.');
        }
        return $quoted;
    }

    private function buildZipArchive(string $zipPath, array $databaseInfo): int
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP ZipArchive wajib aktif untuk backup pada hosting ini.');
        }
        $archive = new ZipArchive();
        $opened = $archive->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            throw new RuntimeException('File ZIP snapshot tidak dapat dibuat (kode '.$opened.').');
        }

        $fileCount = 1;
        try {
            $databaseArchivePath = $databaseInfo['driver'] === 'sqlite' ? 'database/database.sqlite' : 'database/database.sql';
            if (! $archive->addFile($databaseInfo['database_file'], $databaseArchivePath)) {
                throw new RuntimeException('File database gagal ditambahkan ke ZIP.');
            }

            $privateRoot = (string) config('filesystems.disks.local.root', storage_path('app/private'));
            $publicRoot = (string) config('filesystems.disks.public.root', storage_path('app/public'));
            $this->addDirectoryToZip($archive, rtrim($privateRoot, '/\\').'/arsip', 'storage/private/arsip', $fileCount);
            $this->addDirectoryToZip($archive, rtrim($publicRoot, '/\\'), 'storage/public', $fileCount);

            $manifest = [
                'format' => self::FORMAT,
                'created_at' => now()->toIso8601String(),
                'app_name' => config('app.name'),
                'database_driver' => $databaseInfo['driver'],
                'database_tables' => $databaseInfo['tables'] ?? [],
                'file_count' => $fileCount,
                'includes' => ['database', 'storage/private/arsip', 'storage/public'],
                'engine' => 'php-native-zip-pdo',
                'database_objects' => 'tables-only-no-views-triggers-routines',
            ];
            if (! $archive->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))) {
                throw new RuntimeException('Manifest backup gagal ditambahkan.');
            }
        } catch (Throwable $exception) {
            $archive->close();
            @unlink($zipPath);
            throw $exception;
        }

        if (! $archive->close()) {
            throw new RuntimeException('ZIP snapshot gagal diselesaikan. Periksa ruang penyimpanan sementara.');
        }
        if (! is_file($zipPath) || filesize($zipPath) < 100) {
            throw new RuntimeException('ZIP snapshot kosong atau tidak lengkap.');
        }
        return $fileCount;
    }

    private function addDirectoryToZip(ZipArchive $archive, string $source, string $archivePrefix, int &$fileCount): void
    {
        $archive->addEmptyDir($archivePrefix);
        if (! is_dir($source)) return;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isLink()) continue;
            $fullPath = $item->getPathname();
            $relative = ltrim(substr($fullPath, strlen(rtrim($source, '/\\'))), '/\\');
            if ($relative === '' || str_contains($relative, "\0")) continue;
            $localName = $archivePrefix.'/'.$relative;
            if ($item->isDir()) {
                $archive->addEmptyDir($localName);
            } elseif ($item->isFile()) {
                if (! $archive->addFile($fullPath, $localName)) {
                    throw new RuntimeException('File gagal ditambahkan ke ZIP: '.$relative);
                }
                $fileCount++;
            }
        }
    }

    /** Safely extract a PHP-native ZIP snapshot; no shell or Phar is required. */
    private function extractZipSnapshot(string $zipPath, string $extractDir): void
    {
        $zip = new ZipArchive();
        $opened = $zip->open($zipPath);
        if ($opened !== true) {
            throw new RuntimeException('ZIP snapshot tidak dapat dibuka.');
        }
        try {
            $maxEntries = (int) config('backup.max_restore_entries', 50000);
            $maxBytes = (int) config('backup.max_restore_uncompressed_bytes', 10737418240);
            if ($zip->numFiles > $maxEntries) {
                throw new RuntimeException('Backup memiliki terlalu banyak item.');
            }

            $totalBytes = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                if (! is_array($stat)) throw new RuntimeException('Metadata item ZIP tidak dapat dibaca.');
                $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));
                if ($name === '' || str_starts_with($name, '/') || preg_match('#(^|/)\.\.?(/|$)#', $name) || str_contains($name, "\0")) {
                    throw new RuntimeException('ZIP berisi jalur file yang tidak aman.');
                }
                $allowed = in_array(rtrim($name, '/'), [
                    'manifest.json', 'database', 'database/database.sql', 'database/database.sqlite',
                    'storage', 'storage/private', 'storage/private/arsip', 'storage/public',
                ], true)
                    || str_starts_with($name, 'storage/private/arsip/')
                    || str_starts_with($name, 'storage/public/');
                if (! $allowed) throw new RuntimeException('ZIP berisi item di luar struktur backup SIMPAN-AWAS.');

                $totalBytes += (int) ($stat['size'] ?? 0);
                if ($totalBytes > $maxBytes) throw new RuntimeException('Ukuran hasil ekstraksi backup melebihi batas konfigurasi.');

                $opsys = 0;
                $attributes = 0;
                if ($zip->getExternalAttributesIndex($index, $opsys, $attributes) && $opsys === ZipArchive::OPSYS_UNIX) {
                    $fileType = ($attributes >> 16) & 0170000;
                    if ($fileType === 0120000) throw new RuntimeException('ZIP berisi symbolic link yang tidak diizinkan.');
                }
            }

            if ($zip->locateName('manifest.json') === false) throw new RuntimeException('Manifest backup tidak ditemukan di ZIP.');
            if (! $zip->extractTo($extractDir)) throw new RuntimeException('Isi ZIP gagal diekstrak.');
        } finally {
            $zip->close();
        }
    }

    private function importMysqlSqlFile(string $source): void
    {
        $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
        $handle = fopen($source, 'rb');
        if ($handle === false) throw new RuntimeException('Dump database tidak dapat dibaca.');

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        try {
            $buffer = '';
            $quote = null;
            $escaped = false;
            $lineComment = false;
            $blockComment = false;
            $previous = '';
            $delimiter = ';';

            while (($char = fgetc($handle)) !== false) {
                if ($lineComment) {
                    if ($char === "\n") { $lineComment = false; $buffer .= "\n"; }
                    continue;
                }
                if ($blockComment) {
                    if ($previous === '*' && $char === '/') { $blockComment = false; $previous = ''; }
                    else { $previous = $char; }
                    continue;
                }
                if ($quote !== null) {
                    $buffer .= $char;
                    if ($escaped) { $escaped = false; continue; }
                    if ($char === '\\') { $escaped = true; continue; }
                    if ($char === $quote) $quote = null;
                    continue;
                }

                if ($char === "'") { $quote = "'"; $buffer .= $char; continue; }
                if ($char === '"') { $quote = '"'; $buffer .= $char; continue; }
                if ($char === '`') { $quote = '`'; $buffer .= $char; continue; }
                if ($char === '#') { $lineComment = true; continue; }
                if ($char === '/') {
                    $next = fgetc($handle);
                    if ($next === '*') { $blockComment = true; $previous = ''; continue; }
                    $buffer .= $char;
                    if ($next !== false) $buffer .= $next;
                    continue;
                }
                if ($char === '-') {
                    $next = fgetc($handle);
                    if ($next === '-') {
                        $after = fgetc($handle);
                        if ($after === false) { $lineComment = true; continue; }
                        if ($after === "\n") { $buffer .= "\n"; continue; }
                        if (ctype_space($after)) { $lineComment = true; continue; }
                        $buffer .= '--'.$after;
                        continue;
                    }
                    $buffer .= '-';
                    if ($next !== false) $buffer .= $next;
                    continue;
                }

                if ($char === "\n" && trim($buffer) === '') { $buffer = ''; continue; }
                if ($char === "\n" && preg_match('/^\s*DELIMITER\s+(.+)\s*$/i', trim($buffer), $match)) {
                    $delimiter = trim($match[1]);
                    if ($delimiter === '') throw new RuntimeException('Direktif delimiter SQL tidak valid.');
                    $buffer = '';
                    continue;
                }

                $buffer .= $char;
                if ($delimiter === ';' && $char === ';') {
                    $statement = trim(substr($buffer, 0, -1));
                    $buffer = '';
                    $this->executeRestoredSqlStatement($pdo, $statement);
                } elseif ($delimiter !== '' && str_ends_with($buffer, $delimiter)) {
                    $statement = trim(substr($buffer, 0, -strlen($delimiter)));
                    $buffer = '';
                    $this->executeRestoredSqlStatement($pdo, $statement);
                }
            }

            $remaining = trim($buffer);
            if ($remaining !== '') $this->executeRestoredSqlStatement($pdo, rtrim($remaining, ';'));
        } finally {
            fclose($handle);
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function executeRestoredSqlStatement(PDO $pdo, string $statement): void
    {
        $statement = trim($statement);
        if ($statement === '') return;
        if (preg_match('/^(DELIMITER|LOCK TABLES|UNLOCK TABLES|CREATE DATABASE|USE\s)/i', $statement)) {
            if (stripos($statement, 'DELIMITER') === 0) {
                throw new RuntimeException('Backup SQL lama berisi stored procedure/trigger dengan delimiter khusus. Impor PHP murni tidak dapat memulihkan routine tersebut.');
            }
            return;
        }
        if (preg_match('/^SET\s+/i', $statement)
            && ! preg_match('/^SET\s+(NAMES|FOREIGN_KEY_CHECKS)/i', $statement)) {
            return;
        }
        if (preg_match('/^ALTER\s+TABLE\s+.+\s+(DISABLE|ENABLE)\s+KEYS$/i', $statement)) return;
        $pdo->exec($statement);
    }

    private function restoreDatabase(string $extractDir, string $driver, array $backupTables = []): void
    {
        if ($driver === 'sqlite') {
            $source = $extractDir.'/database/database.sqlite';
            if (! is_file($source)) throw new RuntimeException('File database SQLite tidak ditemukan di backup.');
            $connection = (array) config('database.connections.sqlite', []);
            $destination = (string) ($connection['database'] ?? '');
            if ($destination === '' || $destination === ':memory:') throw new RuntimeException('Target SQLite tidak dapat dipulihkan.');
            if (! str_starts_with($destination, DIRECTORY_SEPARATOR)) $destination = base_path($destination);
            File::ensureDirectoryExists(dirname($destination));
            if (! copy($source, $destination)) throw new RuntimeException('Database SQLite gagal dipulihkan.');
            return;
        }

        $source = $extractDir.'/database/database.sql';
        if (! is_file($source)) throw new RuntimeException('Dump database tidak ditemukan di dalam backup.');
        if (! in_array($driver, ['mysql', 'mariadb'], true)) throw new RuntimeException('Driver database tidak didukung untuk pemulihan PHP murni.');
        $this->importMysqlSqlFile($source);
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

    private function decryptFile(string $source, string $destination, string $secret): string
    {
        $input = fopen($source, 'rb');
        $output = fopen($destination, 'wb');
        if (! $input || ! $output) {
            if (is_resource($input)) fclose($input);
            if (is_resource($output)) fclose($output);
            throw new RuntimeException('File backup terenkripsi tidak dapat dibuka.');
        }

        try {
            $magic = $this->readExact($input, 5);
            if (! in_array($magic, [self::MAGIC, self::LEGACY_MAGIC], true)) {
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
                if ($lengthBytes === '' || $lengthBytes === false) break;
                if (strlen($lengthBytes) !== 4) throw new RuntimeException('Backup terenkripsi terpotong.');
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

                if ($length < 1 || $length > $maxChunk) throw new RuntimeException('Ukuran blok backup tidak valid.');
                $cipher = $this->readExact($input, $length);
                $tag = $this->readExact($input, 16);
                $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $noncePrefix.$counterBytes, $tag, $header.$counterBytes);
                if ($plain === false) throw new RuntimeException('Autentikasi enkripsi gagal. Kunci backup salah atau file berubah.');
                $this->writeAll($output, $plain);
                $counter++;
            }

            if ($counter === 0 || ! $ended) throw new RuntimeException('Backup tidak lengkap atau tidak memiliki penanda akhir autentik.');
            return $magic;
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
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP ZipArchive wajib aktif. Hubungi hosting untuk mengaktifkan ekstensi zip.');
        }
        if (! extension_loaded('openssl') || ! function_exists('openssl_encrypt') || ! function_exists('openssl_decrypt')) {
            throw new RuntimeException('Ekstensi PHP OpenSSL wajib aktif pada hosting.');
        }
        $driver = (string) config('database.connections.'.config('database.default').'.driver', config('database.default'));
        if (in_array($driver, ['mysql', 'mariadb'], true) && ! extension_loaded('pdo_mysql')) {
            throw new RuntimeException('Ekstensi PDO MySQL wajib aktif pada hosting.');
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
