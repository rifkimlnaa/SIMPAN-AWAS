# Checklist keamanan SIMPAN-AWAS untuk hosting berbayar

Dokumen ini adalah panduan deployment umum untuk hosting PHP/Laravel dengan HTTPS dan database MySQL. Sesuaikan dengan panel hosting yang dipakai.

## 1. Konfigurasi environment production

Jangan upload `.env` dari komputer lokal apa adanya. Buat `.env` khusus di server dan isi nilai rahasia langsung melalui File Manager/SSH/panel hosting.

Minimal:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-resmi-anda

SESSION_DRIVER=database
SESSION_LIFETIME=60
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

LOG_LEVEL=warning
FILESYSTEM_DISK=local
```

- `APP_KEY` harus unik per instalasi. Buat sekali dengan `php artisan key:generate --show`, lalu simpan di `.env` server. Jangan membagikannya dan jangan mengganti setiap deployment.
- Isi `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai database hosting.
- Jangan isi `SEED_ADMIN_PASSWORD` dengan password contoh atau password yang mudah ditebak. Seeder proyek membuat password acak jika variabel itu kosong; catat kredensial yang dicetak terminal dan segera ubah setelah login pertama.
- Aktifkan HTTPS/SSL di panel hosting sebelum `SESSION_SECURE_COOKIE=true` diberlakukan.

## 2. Struktur direktori

- Arahkan document root domain ke folder `public/` milik Laravel jika hosting mengizinkan.
- Folder `app`, `bootstrap`, `config`, `database`, `resources`, `routes`, `storage`, `vendor`, dan `.env` harus berada di luar web root publik.
- Jangan membuat symlink `storage/app/private` ke `public`.
- Disk `local` proyek menyimpan berkas arsip di `storage/app/private`; hanya foto profil dan aset latar yang memang publik yang menggunakan disk `public`.
- Beri hak tulis minimum yang diperlukan kepada `storage/` dan `bootstrap/cache/`. Hindari permission `777`.
- Pastikan directory listing dimatikan.

## 3. Perintah deployment

Jalankan dari direktori aplikasi (melalui SSH/Terminal hosting atau secara lokal sebelum mengunggah paket deploy):

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate --show
php artisan migrate --force
php artisan storage:link
php artisan optimize
composer audit
```

`key:generate --show` hanya menampilkan key; salin satu kali ke `.env` server. Jangan membuat key baru jika aplikasi sudah mempunyai data/session terenkripsi.

Sebelum deploy, perbarui Laravel ke versi patch yang memperbaiki advisory di bawah ini. Dengan constraint `^12.0` yang ada, jalankan:

```bash
composer update laravel/framework --with-dependencies
composer audit
composer show laravel/framework
```

Pastikan framework yang terkunci minimal `12.72.0` atau versi patch yang lebih baru pada major 12. Bila hosting tidak menyediakan Composer, jalankan Composer pada komputer yang sesuai lalu unggah `composer.lock` dan `vendor/` hasil `composer install --no-dev` secara utuh.

## 4. Akun dan akses

- Gunakan akun berbeda untuk setiap petugas; jangan berbagi satu akun.
- Gunakan password unik minimal 12 karakter; jangan menggunakan password yang sama dengan email atau akun lain.
- Nonaktifkan/hapus akun petugas yang sudah tidak berwenang sesuai kebijakan kantor.
- Batasi akses panel hosting, database, dan backup hanya kepada administrator yang berwenang.
- Aktifkan 2FA pada panel hosting, registrar domain, dan layanan database bila tersedia. 2FA di dalam aplikasi belum termasuk dalam patch ini.

## 5. Dokumen, upload, dan backup

- Pertahankan berkas arsip pada storage privat dan selalu akses melalui route yang menjalankan authorization policy.
- Jangan membuat URL publik permanen untuk dokumen laporan, identitas pelapor, saksi, atau bukti.
- Pertahankan allowlist tipe file dan batas ukuran upload. Tambahkan antivirus/malware scanning bila layanan hosting menyediakan; fitur scanner belum termasuk dalam patch ini.
- Backup database dan seluruh `storage/app/private` secara terjadwal. Enkripsi backup, simpan salinan terpisah dari server utama, batasi akses, dan uji pemulihan secara berkala.
- Tentukan masa retensi log download/IP sesuai kebijakan perlindungan data instansi.

## 6. PHP dan web server

- Laravel 12 memerlukan PHP 8.2 atau lebih baru. Pastikan ekstensi PHP Laravel dan Dompdf yang dibutuhkan aktif, terutama `openssl`, `pdo_mysql`, `mbstring`, `fileinfo`, `dom`, `xml`, `ctype`, `tokenizer`, dan `json`.
- Atur `display_errors=Off` dan `log_errors=On` di production.
- Batasi ukuran request di PHP/web server agar sejalan dengan batas upload aplikasi.
- Jika HTTPS dihentikan oleh reverse proxy, pastikan konfigurasi trusted proxies hosting benar sehingga Laravel mengenali request HTTPS dan mengirim HSTS.

## 7. Setelah deployment

1. Tes login salah berulang: pembatasan percobaan login harus bekerja.
2. Tes akun petugas mencoba membuka arsip milik petugas lain: hasil harus 403/404.
3. Tes akses langsung ke file privat tanpa login: harus ditolak.
4. Tes upload ekstensi yang tidak diizinkan: harus ditolak.
5. Periksa response header HTTPS: `Strict-Transport-Security`, `X-Content-Type-Options`, `X-Frame-Options`, dan `Content-Security-Policy`.
6. Pastikan halaman error tidak menampilkan stack trace atau konfigurasi rahasia.
7. Pastikan backup dapat dipulihkan.

## 8. Batas pemeriksaan ini

Patch ini memperkuat konfigurasi dan beberapa kontrol aplikasi, tetapi bukan sertifikasi keamanan atau penetration test. Lakukan pemeriksaan dependensi (`composer audit`), uji akses berdasarkan role, dan uji pemulihan backup sebelum aplikasi digunakan untuk dokumen resmi.
