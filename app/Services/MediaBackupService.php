<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use ZipArchive;

/**
 * Backup Media: satu file ZIP berisi database + seluruh berkas web (arsip & media).
 * Murni PHP (PDO + ZipArchive); tidak memakai proc_open, exec, atau mysqldump.
 * Jadwal disimpan di storage/app/private/media-backup.json (tanpa migrasi).
 */
class MediaBackupService
{
    public const ZONA = 'Asia/Jakarta';

    private const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    // ------------------------------------------------------------------ jadwal

    public function pengaturanPath(): string
    {
        return storage_path('app/private/media-backup.json');
    }

    public function pengaturan(): array
    {
        $default = ['frekuensi' => 'off', 'jam' => '01:00', 'hari' => 0, 'tanggal' => 1, 'diatur_pada' => null, 'terakhir_unduh' => null,
            'tujuan' => 'keduanya', 'drive_status' => null, 'drive_waktu' => null, 'drive_pesan' => null];
        $path = $this->pengaturanPath();
        $data = is_file($path) ? json_decode((string) @file_get_contents($path), true) : null;

        return array_merge($default, is_array($data) ? $data : []);
    }

    public function simpanPengaturan(string $frekuensi, string $jam, int $hari, int $tanggal, string $tujuan = 'keduanya'): array
    {
        $p = $this->pengaturan();
        $p['frekuensi'] = in_array($frekuensi, ['off', 'harian', 'mingguan', 'bulanan'], true) ? $frekuensi : 'off';
        $p['jam'] = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $jam) ? $jam : '01:00';
        $p['hari'] = max(0, min(6, $hari));
        $p['tanggal'] = max(1, min(28, $tanggal));
        $p['tujuan'] = in_array($tujuan, ['unduh', 'drive', 'keduanya'], true) ? $tujuan : 'keduanya';
        $p['diatur_pada'] = now()->toIso8601String();
        $this->tulis($p);

        return $p;
    }

    private function tulis(array $p): void
    {
        File::ensureDirectoryExists(dirname($this->pengaturanPath()), 0775, true);
        file_put_contents($this->pengaturanPath(), json_encode($p, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    /** Waktu jadwal terakhir yang sudah lewat (atau null bila jadwal nonaktif). */
    public function jadwalTerakhir(?array $p = null, ?CarbonImmutable $sekarang = null): ?CarbonImmutable
    {
        $p ??= $this->pengaturan();
        if ($p['frekuensi'] === 'off') {
            return null;
        }

        $sekarang = ($sekarang ?? CarbonImmutable::now())->setTimezone(self::ZONA);
        [$j, $m] = array_map('intval', explode(':', $p['jam']));
        $calon = $sekarang->setTime($j, $m, 0);

        if ($p['frekuensi'] === 'harian') {
            return $calon > $sekarang ? $calon->subDay() : $calon;
        }

        if ($p['frekuensi'] === 'mingguan') {
            $calon = $calon->subDays(($calon->dayOfWeek - (int) $p['hari'] + 7) % 7);

            return $calon > $sekarang ? $calon->subWeek() : $calon;
        }

        $calon = $sekarang->setDay((int) $p['tanggal'])->setTime($j, $m, 0);

        return $calon > $sekarang ? $calon->subMonthNoOverflow()->setDay((int) $p['tanggal']) : $calon;
    }

    /** Jadwal berikutnya (untuk ditampilkan). */
    public function jadwalBerikutnya(?array $p = null): ?CarbonImmutable
    {
        $p ??= $this->pengaturan();
        $terakhir = $this->jadwalTerakhir($p);
        if (! $terakhir) {
            return null;
        }

        return match ($p['frekuensi']) {
            'harian' => $terakhir->addDay(),
            'mingguan' => $terakhir->addWeek(),
            default => $terakhir->addMonthNoOverflow()->setDay((int) $p['tanggal']),
        };
    }

    public function jatuhTempo(?array $p = null): bool
    {
        $p ??= $this->pengaturan();
        $jadwal = $this->jadwalTerakhir($p);
        if (! $jadwal) {
            return false;
        }

        $acuan = $p['terakhir_unduh'] ?: $p['diatur_pada'];
        if (! $acuan) {
            return false;
        }

        return CarbonImmutable::parse($acuan) < $jadwal;
    }

    /**
     * Tandai backup terjadwal sebagai diambil (sekali saja, aman dari dua tab sekaligus).
     * Mengembalikan true bila pemanggil boleh membuat & mengunduh ZIP sekarang.
     */
    public function klaimJadwal(): bool
    {
        File::ensureDirectoryExists(dirname($this->pengaturanPath()), 0775, true);
        $kunci = fopen(storage_path('app/private/media-backup.lock'), 'c');
        if (! $kunci) {
            return false;
        }

        try {
            if (! flock($kunci, LOCK_EX)) {
                return false;
            }
            $p = $this->pengaturan();
            if (! $this->jatuhTempo($p)) {
                return false;
            }
            $p['terakhir_unduh'] = now()->toIso8601String();
            $this->tulis($p);

            return true;
        } finally {
            fclose($kunci);
        }
    }

    public function kirimKeDrive(?array $p = null): bool
    {
        return in_array(($p ?? $this->pengaturan())['tujuan'], ['drive', 'keduanya'], true);
    }

    public function kirimUnduhan(?array $p = null): bool
    {
        return in_array(($p ?? $this->pengaturan())['tujuan'], ['unduh', 'keduanya'], true);
    }

    /** Simpan hasil unggahan Drive terakhir untuk ditampilkan di halaman. */
    public function catatDrive(bool $berhasil, string $pesan): void
    {
        $p = $this->pengaturan();
        $p['drive_status'] = $berhasil ? 'berhasil' : 'gagal';
        $p['drive_waktu'] = now()->toIso8601String();
        $p['drive_pesan'] = mb_substr($pesan, 0, 300);
        $this->tulis($p);
    }

    public function catatUnduhan(): void
    {
        $p = $this->pengaturan();
        $p['terakhir_unduh'] = now()->toIso8601String();
        $this->tulis($p);
    }

    public function labelJadwal(?array $p = null): string
    {
        $p ??= $this->pengaturan();

        return match ($p['frekuensi']) {
            'harian' => 'Setiap hari pukul '.$p['jam'].' WIB',
            'mingguan' => 'Setiap hari '.self::HARI[(int) $p['hari']].' pukul '.$p['jam'].' WIB',
            'bulanan' => 'Setiap tanggal '.(int) $p['tanggal'].' pukul '.$p['jam'].' WIB',
            default => 'Nonaktif',
        };
    }

    // ------------------------------------------------------------------ pembuatan ZIP

    /** Buat ZIP dan kembalikan path-nya. Folder kerja dihapus otomatis di akhir request. */
    public function buatZip(): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP ZipArchive belum aktif di hosting ini.');
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }

        $kerja = storage_path('app/private/.media-backup-work/'.Str::uuid());
        File::ensureDirectoryExists($kerja, 0700, true);
        app()->terminating(fn () => File::deleteDirectory($kerja));

        $zipPath = $kerja.'/simpan-awas-backup-'.now(self::ZONA)->format('Ymd-His').'.zip';
        $db = $this->dumpDatabase($kerja);

        $zip = new ZipArchive();
        $this->buka($zip, $zipPath);
        $jumlah = 0;

        if (! $zip->addFile($db['file'], $db['nama'])) {
            throw new RuntimeException('Database gagal dimasukkan ke ZIP.');
        }
        $jumlah++;

        $private = rtrim((string) config('filesystems.disks.local.root', storage_path('app/private')), '/\\');
        $public = rtrim((string) config('filesystems.disks.public.root', storage_path('app/public')), '/\\');

        $this->tambahFolder($zip, $zipPath, $private, 'berkas/private', $jumlah, ['.media-backup-work', '.backup-work', '.restore-work', 'backups']);
        $this->tambahFolder($zip, $zipPath, $public, 'berkas/public', $jumlah, []);

        $zip->addFromString('INFO.txt', "Backup SIMPAN-AWAS\nDibuat: ".now(self::ZONA)->format('d/m/Y H:i')." WIB\nIsi: database (".$db['nama'].") dan folder berkas/.\nSimpan file ini di tempat aman: berisi data asli.\n");
        if (! $zip->close()) {
            throw new RuntimeException('ZIP gagal diselesaikan. Periksa ruang penyimpanan hosting.');
        }

        return $zipPath;
    }

    private function buka(ZipArchive $zip, string $path, bool $baru = true): void
    {
        $r = $zip->open($path, $baru ? ZipArchive::CREATE | ZipArchive::OVERWRITE : ZipArchive::CREATE);
        if ($r !== true) {
            throw new RuntimeException('File ZIP tidak dapat dibuat (kode '.$r.').');
        }
    }

    private function tambahFolder(ZipArchive $zip, string $zipPath, string $sumber, string $prefix, int &$jumlah, array $kecuali): void
    {
        $zip->addEmptyDir($prefix);
        if (! is_dir($sumber)) {
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($sumber, \FilesystemIterator::SKIP_DOTS),
                fn (\SplFileInfo $f) => ! ($f->isDir() && in_array($f->getFilename(), $kecuali, true)) && ! $f->isLink()
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $dalamBatch = 0;
        foreach ($it as $f) {
            $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($sumber) + 1));
            if ($f->isDir()) {
                $zip->addEmptyDir($prefix.'/'.$rel);
            } elseif ($f->isFile() && $f->isReadable() && ! in_array($f->getFilename(), ['.gitignore', 'media-backup.json', 'media-backup.lock'], true)) {
                $zip->addFile($f->getPathname(), $prefix.'/'.$rel);
                $jumlah++;
                // ZipArchive menahan semua file terbuka sampai close(); tutup berkala agar tidak melewati batas hosting.
                if (++$dalamBatch >= 200) {
                    if (! $zip->close()) {
                        throw new RuntimeException('ZIP gagal ditulis di tengah proses.');
                    }
                    $this->buka($zip, $zipPath, false);
                    $dalamBatch = 0;
                }
            }
        }
    }

    // ------------------------------------------------------------------ database (PDO)

    /** @return array{file:string,nama:string} */
    private function dumpDatabase(string $kerja): array
    {
        $nama = (string) config('database.default');
        $conn = (array) config('database.connections.'.$nama, []);
        $driver = (string) ($conn['driver'] ?? $nama);

        if ($driver === 'sqlite') {
            $db = (string) ($conn['database'] ?? '');
            if ($db === '' || $db === ':memory:') {
                throw new RuntimeException('Database SQLite in-memory tidak dapat dicadangkan.');
            }
            if (! str_starts_with($db, DIRECTORY_SEPARATOR)) {
                $db = base_path($db);
            }
            $tujuan = $kerja.'/database.sqlite';
            if (! is_file($db) || ! copy($db, $tujuan)) {
                throw new RuntimeException('File database SQLite tidak dapat disalin.');
            }

            return ['file' => $tujuan, 'nama' => 'database/database.sqlite'];
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Jenis database "'.$driver.'" belum didukung untuk backup.');
        }

        $pdo = DB::connection($nama)->getPdo();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $tujuan = $kerja.'/database.sql';
        $h = fopen($tujuan, 'wb');
        if (! $h) {
            throw new RuntimeException('File dump database tidak dapat dibuat.');
        }

        try {
            fwrite($h, "-- SIMPAN-AWAS backup ".now(self::ZONA)->toDateTimeString()."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");
            $tabel = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
            foreach ($tabel as [$t]) {
                $q = '`'.str_replace('`', '``', $t).'`';
                $buat = $pdo->query('SHOW CREATE TABLE '.$q)->fetch(PDO::FETCH_NUM);
                fwrite($h, "DROP TABLE IF EXISTS $q;\n".$buat[1].";\n\n");

                $stmt = $pdo->query('SELECT * FROM '.$q);
                $batch = [];
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    $batch[] = '('.implode(',', array_map(
                        fn ($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : $pdo->quote((string) $v)),
                        $row
                    )).')';
                    if (count($batch) >= 200) {
                        fwrite($h, "INSERT INTO $q VALUES\n".implode(",\n", $batch).";\n");
                        $batch = [];
                    }
                }
                if ($batch) {
                    fwrite($h, "INSERT INTO $q VALUES\n".implode(",\n", $batch).";\n");
                }
                fwrite($h, "\n");
            }
            fwrite($h, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($h);
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }

        return ['file' => $tujuan, 'nama' => 'database/database.sql'];
    }
}
