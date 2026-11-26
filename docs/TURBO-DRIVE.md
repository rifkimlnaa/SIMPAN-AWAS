# Navigasi Turbo Drive SIMPAN-AWAS

## Ringkasan

SIMPAN-AWAS menggunakan Hotwire Turbo Drive 8.0.23 untuk navigasi GET internal. Perpindahan antarhalaman mengambil HTML melalui fetch dan mengganti isi dokumen tanpa memuat ulang seluruh dokumen browser.

## Batas keamanan dan kompatibilitas

- Seluruh form POST/PUT/PATCH/DELETE diberi `data-turbo="false"`. Penyimpanan, upload, hapus, logout, backup ZIP, dan pengaturan tetap memakai perilaku form browser biasa.
- Link PDF, Word, unduhan berkas, dan OAuth Google dikecualikan dari Turbo.
- Link internal yang menampilkan halaman HTML tetap menggunakan Turbo.
- Cache snapshot Turbo dinonaktifkan pada layout terautentikasi (`turbo-cache-control=no-cache`) karena aplikasi menyimpan dokumen arsip dan informasi internal. Navigasi tetap tanpa full reload, tetapi tombol Back/Forward akan mengambil halaman dari server alih-alih menampilkan snapshot sensitif dari memori.
- JavaScript global (Alpine, SweetAlert2, jQuery, DataTables, dan handler navigasi) dimuat sekali. Inisialisasi per halaman tetap berjalan saat event `turbo:load`/render body.
- DataTables dibersihkan sebelum body diganti; dropdown navbar memakai event delegation; listener resize grafik dashboard dipasang satu kali.

## File yang berubah

- `resources/views/layouts/app.blade.php`
- `resources/views/partials/scripts.blade.php`
- `resources/views/partials/scripts-app.blade.php`
- Form-form di `resources/views/**` (pengecualian Turbo pada form)
- Link output file dan Google OAuth (pengecualian Turbo)
- `public/js/tabler-nav.js`
- `public/js/dashboard-charts.js`
- `public/css/tabler.css`

## Deployment

Tidak memerlukan perubahan Composer, npm, migrasi database, `.env`, maupun cron. Setelah file digabungkan dan di-push ke GitHub, tarik perubahan di cPanel dan bersihkan cache Laravel.

```bash
php artisan optimize:clear
php artisan view:clear
```

Catatan: layout memuat Turbo 8.0.23 dari jsDelivr dengan versi dipatok. Navigasi akan kembali ke navigasi browser standar apabila CDN Turbo gagal dimuat; link dan form tetap berfungsi karena server Laravel tetap menjadi sumber halaman.
