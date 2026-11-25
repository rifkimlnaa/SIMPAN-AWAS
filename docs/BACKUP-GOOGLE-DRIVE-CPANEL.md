# Backup & Pemulihan SIMPAN-AWAS: cPanel + Google Drive

## Apa yang ditambahkan

- Panel penggunaan storage aplikasi (database, dokumen arsip privat, media publik, dan file backup).
- Panel kuota akun cPanel melalui UAPI `StatsBar/get_stats` jika API URL dan token disediakan.
- Unduhan paket ZIP. ZIP hanya berisi snapshot `.sawb` yang sudah dienkripsi AES-256-GCM, manifest checksum, dan petunjuk pemulihan; tidak berisi `.env`, APP_KEY, atau kredensial.
- Koneksi Google Drive melalui OAuth Web Server dengan scope `drive.file`; refresh token disimpan terenkripsi dalam tabel `pengaturans`.
- Upload manual per backup dan opsi mengirim backup manual ke Drive; backup terjadwal diunggah otomatis bila `BACKUP_GOOGLE_DRIVE_AUTO_UPLOAD=true` dan Drive terhubung.
- Perintah `backup:import-zip` untuk mendaftarkan snapshot dari paket ZIP yang diunduh dari komputer atau Google Drive.

## 1. Pengaturan cPanel storage quota (opsional)

Buat API Token di cPanel: **Security → Manage API Tokens**. Simpan token sebagai rahasia. Tambahkan ke `.env` (jangan ke Git):

```env
BACKUP_CPANEL_API_URL=https://hostname-cpanel-kamu:2083
BACKUP_CPANEL_USERNAME=username_cpanel
BACKUP_CPANEL_API_TOKEN=token_rahasia
```

Aplikasi memanggil `GET /execute/StatsBar/get_stats?display=diskusage` dengan header `Authorization: cpanel USER:TOKEN`. Pastikan host memakai HTTPS dan sertifikat TLS valid. Jika provider menonaktifkan API atau token tidak memiliki izin, UI tetap menampilkan pemakaian storage aplikasi dan menandai kuota cPanel tidak tersedia. `disk_total_space()` hanya dilabeli sebagai kapasitas volume server, bukan kuota akun hosting.

## 2. Pengaturan Google Drive

1. Buat project di Google Cloud Console.
2. Enable **Google Drive API**.
3. Siapkan OAuth consent screen dan tambahkan akun Google pengelola sebagai test user jika aplikasi masih berstatus Testing. Untuk penggunaan jangka panjang, selesaikan proses publishing/verification sesuai jenis akun dan kebijakan Google.
4. Buat OAuth Client ID tipe **Web application**.
5. Tambahkan Authorized redirect URI persis sama dengan `https://DOMAIN-KAMU/backup/google/callback`.
6. Tambahkan ke `.env`:

```env
GOOGLE_DRIVE_CLIENT_ID=...apps.googleusercontent.com
GOOGLE_DRIVE_CLIENT_SECRET=...
GOOGLE_DRIVE_REDIRECT_URI=https://DOMAIN-KAMU/backup/google/callback
BACKUP_GOOGLE_DRIVE_AUTO_UPLOAD=true
BACKUP_GOOGLE_DRIVE_UPLOAD_TIMEOUT=1800
```

7. Jalankan `php artisan config:clear` lalu buka **Backup & Pemulihan → Hubungkan Google Drive** sebagai Admin dan setujui akses.

Aplikasi meminta scope `drive.file` dan `access_type=offline`. Google dapat memberikan refresh token untuk upload terjadwal. Token disimpan menggunakan Laravel Crypt, jadi jangan mengganti APP_KEY tanpa prosedur migrasi token.

## 3. Jadwal otomatis cPanel

File `routes/backup-schedule.php` sekarang diregistrasikan dari `routes/console.php`. Tambahkan satu cron job di cPanel → Cron Jobs, setiap menit:

```cron
* * * * * cd /home/CPANELUSER/PATH/TO/arsip-hp3s && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Sesuaikan path PHP dan folder sesuai cPanel. Periksa `php artisan schedule:list` untuk memastikan jadwal terdaftar. Jadwal backup: harian 01.00 WIB, mingguan Minggu 02.00 WIB, bulanan tanggal 1 pukul 03.00 WIB; cleanup 04.00 WIB.

## 4. ZIP dan pemulihan

Tombol unduh menghasilkan ZIP dengan file terenkripsi `.sawb` di dalamnya. Untuk memulihkan paket yang disimpan di komputer/Google Drive:

```bash
php artisan backup:import-zip /path/ke/simpan-awas-backup.zip
php artisan backup:restore-file backups/NAMA-FILE.sawb
```

Perintah restore meminta konfirmasi interaktif dan membuat backup pengaman sebelum mengganti database/storage. Jalankan hanya ketika database target dan folder storage sudah disiapkan. Pastikan `BACKUP_ENCRYPTION_KEY` sama dengan saat backup dibuat.

## 5. Catatan penyimpanan dan keamanan

- ZIP backup tetap terenkripsi karena payload `.sawb` telah dienkripsi sebelum masuk ZIP.
- Simpan `BACKUP_ENCRYPTION_KEY`, APP_KEY, dan kredensial OAuth di tempat terpisah dari backup. Kehilangan encryption key berarti snapshot tidak dapat didekripsi.
- Akun Google yang menghubungkan OAuth menjadi pemilik file backup Drive; file memakai kuota Drive akun tersebut.
- File backup di Drive tidak dibuat publik.
- Panel cPanel diperbarui berkala, tetapi statistik cPanel dapat memiliki jeda pembaruan.
- Sebelum restore produksi, uji pemulihan pada salinan database dan storage.
