<?php
/**
 * Patch aman untuk menyembunyikan PSPP-10 dari pilihan jenis arsip baru.
 * Jalankan dari root proyek Laravel: php patch-hide-pspp10.php
 * Tidak menghapus definisi PSPP-10 atau arsip lama.
 */

declare(strict_types=1);

$root = __DIR__;
$configPath = $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'arsip.php';
$modelPath = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'Arsip.php';

foreach ([$configPath, $modelPath] as $path) {
    if (!is_file($path) || !is_readable($path) || !is_writable($path)) {
        fwrite(STDERR, "Gagal: file tidak ditemukan/tidak bisa dibaca atau ditulis: {$path}\n");
        exit(1);
    }
}

$config = file_get_contents($configPath);
$model = file_get_contents($modelPath);
if ($config === false || $model === false) {
    fwrite(STDERR, "Gagal membaca file konfigurasi/model.\n");
    exit(1);
}

$configChanged = false;
$modelChanged = false;

// Tandai PSPP-10 agar tidak ditawarkan pada menu/form pilihan jenis arsip.
$marker = "'alat_bukti' => [";
$position = strpos($config, $marker);
if ($position === false) {
    fwrite(STDERR, "Gagal: definisi 'alat_bukti' tidak ditemukan di config/arsip.php. Tidak ada file yang diubah.\n");
    exit(1);
}
$afterMarker = substr($config, $position, 500);
if (!str_contains($afterMarker, "'tampil' => false,")) {
    $config = substr_replace($config, $marker . "\n            'tampil' => false,", $position, strlen($marker));
    $configChanged = true;
}

// jenisUntukKategori() dipakai oleh sidebar, halaman pilih jenis, dropdown form,
// dan validasi controller. Definisi lengkap tetap tersedia melalui semuaJenis().
$old = <<<'OLD'
        return array_filter(
            static::semuaJenis(),
            fn (array $j) => ($j['kategori'] ?? null) === $kategoriJenis
        );
OLD;
$new = <<<'NEW'
        return array_filter(
            static::semuaJenis(),
            fn (array $j) => ($j['kategori'] ?? null) === $kategoriJenis
                && ($j['tampil'] ?? true) !== false
        );
NEW;

$filterMarker = <<<'FILTER'
($j['tampil'] ?? true) !== false
FILTER;
if (!str_contains($model, $filterMarker)) {
    if (substr_count($model, $old) !== 1) {
        fwrite(STDERR, "Gagal: struktur method jenisUntukKategori() tidak cocok dengan versi proyek ini. Tidak ada file yang diubah.\n");
        exit(1);
    }
    $model = str_replace($old, $new, $model, $count);
    if ($count !== 1) {
        fwrite(STDERR, "Gagal menerapkan perubahan model. Tidak ada file yang diubah.\n");
        exit(1);
    }
    $modelChanged = true;
}

if (!$configChanged && !$modelChanged) {
    echo "Tidak ada perubahan: PSPP-10 sudah disembunyikan dari pilihan jenis arsip.\n";
    exit(0);
}

// Simpan salinan pemulihan di storage privat sebelum menulis perubahan.
$backupDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR
    . 'private' . DIRECTORY_SEPARATOR . 'patch-backup-pspp10' . DIRECTORY_SEPARATOR . date('Ymd-His');
if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true) && !is_dir($backupDir)) {
    fwrite(STDERR, "Gagal membuat folder backup perubahan: {$backupDir}\n");
    exit(1);
}

if ($configChanged && !copy($configPath, $backupDir . DIRECTORY_SEPARATOR . 'arsip.php.original')) {
    fwrite(STDERR, "Gagal membuat backup config. Tidak ada file yang diubah.\n");
    exit(1);
}
if ($modelChanged && !copy($modelPath, $backupDir . DIRECTORY_SEPARATOR . 'Arsip.php.original')) {
    fwrite(STDERR, "Gagal membuat backup model. Tidak ada file yang diubah.\n");
    exit(1);
}

// Tulis ke file sementara terlebih dahulu, lalu ganti secara atomik.
$writes = [];
if ($configChanged) $writes[$configPath] = $config;
if ($modelChanged) $writes[$modelPath] = $model;
foreach ($writes as $path => $contents) {
    $tmp = $path . '.patch-tmp';
    if (file_put_contents($tmp, $contents, LOCK_EX) === false || !rename($tmp, $path)) {
        @unlink($tmp);
        fwrite(STDERR, "Gagal menulis perubahan ke {$path}. Salinan asli tersedia di {$backupDir}.\n");
        exit(1);
    }
}

echo "Berhasil: Daftar Alat Bukti Pemohon (PSPP-10) disembunyikan dari menu dan pilihan arsip baru.\n";
echo "Arsip PSPP-10 lama tetap tersimpan; definisi cetak/detail tetap dipertahankan.\n";
echo "Backup file sebelum perubahan: {$backupDir}\n";
echo "Langkah berikutnya: jalankan php artisan config:clear dan php artisan view:clear\n";
