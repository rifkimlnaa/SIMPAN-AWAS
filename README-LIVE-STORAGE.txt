UPDATE LIVE STORAGE HOSTING SIMPAN-AWAS

Isi paket ini hanya untuk menampilkan pemakaian dan kuota disk akun cPanel pada halaman Backup & Pemulihan. Paket ini TIDAK mengubah proses pembuatan backup, restore, Google Drive, database, sidebar, atau fitur arsip.

FILE YANG BERUBAH
- app/Services/CpanelStorageService.php (baru)
- app/Http/Controllers/BackupController.php
- config/services.php
- routes/backup.php
- resources/views/backup/index.blade.php
- .env.example (contoh konfigurasi aman, bukan .env produksi)
- docs/LIVE-STORAGE-CPANEL.md

KONFIGURASI DI .env SERVER
BACKUP_CPANEL_API_URL=https://rinjani.website.co.id:2083
BACKUP_CPANEL_USERNAME=simpanaw
BACKUP_CPANEL_API_TOKEN=TOKEN_CPanel_MILIK_ANDA
BACKUP_CPANEL_TIMEOUT=12
BACKUP_CPANEL_CACHE_SECONDS=45

Jika nilai URL, username, dan token sudah ada di .env cPanel, jangan ubah atau buat token baru. Pastikan token tidak pernah di-commit ke GitHub.

DEPLOY
1. Gabungkan file dari ZIP ke proyek lokal (jangan menyalin .env).
2. Commit dan push ke GitHub.
3. Di Terminal cPanel jalankan:
   cd /home/simpanaw/SIMPAN-AWAS
   git pull --ff-only origin main
   /usr/bin/php artisan optimize:clear
   /usr/bin/php artisan config:cache
4. Login sebagai Admin, buka Backup & Pemulihan. Panel Live Storage akan mengambil data saat halaman dibuka dan memperbaruinya setiap 60 detik. Tombol Perbarui memaksa permintaan baru.

CATATAN
- Statistik berasal dari cPanel UAPI StatsBar::get_stats dan memperlihatkan penggunaan disk akun hosting, bukan hanya folder SIMPAN-AWAS.
- Jika cPanel belum memberikan kuota/angka, panel menampilkan status belum terhubung dan tidak membuat angka perkiraan.
- Statistik cPanel dapat memiliki jeda pembaruan.
- Route hanya bisa diakses Admin dan API Token tetap berada di server.
