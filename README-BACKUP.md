# Modul Backup & Pemulihan Data SIMPAN-AWAS

Modul tambahan Laravel 12, tanpa package Composer tambahan.

## Fitur
- Backup manual dari halaman khusus Admin.
- Backup terjadwal harian, mingguan, bulanan.
- Backup database MySQL/MariaDB melalui PDO (tanpa mysqldump) atau SQLite.
- Pembuatan dan ekstraksi ZIP menggunakan ZipArchive, tanpa shell process.
- Scheduler memakai callback PHP langsung sehingga tidak membutuhkan proc_open.
- Backup file `storage/app/private/arsip` dan seluruh `storage/app/public`.
- Enkripsi AES-256-GCM per blok dengan penanda akhir terautentikasi.
- SHA-256 checksum untuk mendeteksi file yang berubah.
- Backup lokal privat atau disk S3-compatible.
- Riwayat backup, unduh backup terenkripsi, hapus dengan verifikasi password.
- Restore dari backup internal, memerlukan password dan frasa konfirmasi.
- Backup pengaman otomatis sebelum restore.
- Pembersihan otomatis sesuai retensi.

## URL
Setelah registrasi route, menu muncul untuk Admin sebagai **Backup & Pemulihan**.
URL: `/backup`.

## Registrasi route, scheduler, dan menu
Lihat `docs/PATCH-REGISTRASI.txt`.

## Konfigurasi
Lihat `PETUNJUK-INSTALASI.txt` dan `.env.example.additions`.
Kunci enkripsi wajib diisi sebelum tombol backup aktif.

## Perhatian kompatibilitas
- PHP: 8.2+.
- Ekstensi wajib untuk backup baru: ZipArchive, OpenSSL, PDO MySQL (untuk database MySQL/MariaDB).
- Tidak memerlukan `proc_open`, `exec`, `shell_exec`, `system`, `passthru`, `mysqldump`, atau binary `mysql`.
- Restore backup lama SAWB1/TAR.GZ memerlukan ekstensi Phar; backup baru SAWB2 tidak memerlukannya.
- Mesin PHP-native mencadangkan tabel dan data. Jika database memiliki VIEW, TRIGGER, atau ROUTINE, proses akan menolak membuat backup agar tidak menghasilkan snapshot yang tidak lengkap.
- SQLite: file database harus berada pada filesystem yang dapat dibaca/tulis aplikasi.
- S3: disk `s3` harus dikonfigurasi sebelumnya dalam `config/filesystems.php`.
- Untuk otomatisasi, cron hosting perlu menjalankan `php artisan schedule:run` setiap menit.

## Cakupan restore
Restore mengganti database dan direktori `storage/app/private/arsip` serta `storage/app/public` sesuai snapshot. File `.env`, source code, `vendor`, cache, log, dan direktori lain tidak termasuk.

Restore adalah operasi destruktif. Uji dahulu pada staging. Backup pengaman dibuat otomatis, tetapi file backup tetap harus disimpan di lokasi terpisah agar tidak hilang bersama server.

Untuk pemulihan ketika database/hosting hilang total, lihat bagian `PEMULIHAN BENCANA DARI FILE BACKUP .SAWB` di petunjuk instalasi. Command CLI `backup:restore-file` dapat mendaftarkan file dari disk yang dikonfigurasi setelah database kosong dimigrasikan.
