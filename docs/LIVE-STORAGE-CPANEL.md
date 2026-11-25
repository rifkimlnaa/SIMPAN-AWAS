# Live Storage Hosting cPanel

Panel Live Storage mengambil statistik akun hosting dari cPanel UAPI `StatsBar::get_stats` dengan parameter `display=diskusage`.

## Konfigurasi `.env` di server

```dotenv
BACKUP_CPANEL_API_URL=https://hostname-cpanel.example.com:2083
BACKUP_CPANEL_USERNAME=username_cpanel
BACKUP_CPANEL_API_TOKEN=token_cpanel
BACKUP_CPANEL_TIMEOUT=12
BACKUP_CPANEL_CACHE_SECONDS=45
```

Jangan menambahkan API token ke Git, screenshot, atau chat. URL harus memakai HTTPS. Token hanya digunakan di server oleh endpoint admin; token tidak dikirim ke browser.

## Cara kerja

- Endpoint halaman admin: `GET /backup/storage-usage`.
- Data diambil dari `https://HOST:2083/execute/StatsBar/get_stats?display=diskusage`.
- Header autentikasi: `Authorization: cpanel USERNAME:API_TOKEN`.
- Hasil disimpan di cache singkat (default 45 detik) dan panel browser meminta pembaruan setiap 60 detik.
- Endpoint dilindungi middleware `auth` dan `role:admin` dari grup route backup.
- Angka menampilkan penggunaan dan kuota akun cPanel, bukan hanya folder aplikasi SIMPAN-AWAS.

cPanel menyatakan `StatsBar::get_stats` sebagai pengganti yang direkomendasikan untuk fungsi StatsBar API 2 yang lama. Statistik disk cPanel dapat memiliki jeda pembaruan, sehingga istilah “live” di panel berarti refresh API berkala, bukan jaminan perubahan setiap detik.

## Setelah deploy

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:list --path=backup
```

Pastikan token API cPanel masih aktif dan akun hosting mengizinkan akses UAPI StatsBar. Jika API gagal, panel akan menampilkan status belum terhubung tanpa menampilkan token atau detail autentikasi.
