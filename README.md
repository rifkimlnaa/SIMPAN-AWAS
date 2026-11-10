# SIMPAN-AWAS - Bawaslu Kabupaten Banyumas

**Sistem Pengarsipan & Manajemen Dokumen Pengawasan.** Nama aplikasi diatur di `config/arsip.php` (`nama` dan `nama_lengkap`).

Sistem informasi arsip digital untuk penerimaan permohonan penyelesaian sengketa proses pemilu
(kategori **PSAP** dan **PSPP**). Laravel 12, PHP 8.3, MySQL.

Folder ini **bukan proyek Laravel utuh**, hanya kode aplikasinya (model, controller, view, migrasi, route).
Folder `vendor/` dan file inti framework dibuat Composer pada langkah 1.

> **Penting: pakai proyek Laravel yang baru dan kosong.** Jangan ditimpakan ke folder yang sudah berisi
> versi paket sebelumnya (nama file migrasi dan model berbeda, sehingga bentrok). Hapus folder lama,
> atau buat folder baru dengan nama lain.

## 1. Pemasangan

```bash
composer create-project laravel/laravel arsip-hp3s "^12.0"
cd arsip-hp3s
```

Salin **seluruh isi** folder `arsip-hp3s` dari ZIP ini ke dalam proyek tadi (pilih *replace/timpa* bila ditanya),
lalu pasang library pembuat PDF:

```bash
composer require barryvdh/laravel-dompdf
```

Buat database kosong, misalnya `arsip_hp3s` (collation `utf8mb4_unicode_ci`), lalu ubah `.env`:

```env
APP_NAME="SIMPAN-AWAS"
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arsip_hp3s
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan (di PowerShell, selalu diawali `php`):

```bash
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Password admin **ditampilkan sekali** di terminal saat `--seed` (username: `admin`). Catat, lalu ganti lewat
menu *Profil & keamanan*. Ingin password sendiri? Isi `SEED_ADMIN_PASSWORD=...` di `.env` sebelum seeding.

Memperbarui dari versi sebelumnya (data tetap aman)? Timpa file, lalu jalankan `php artisan migrate` saja.

Sudah pernah menjalankan `migrate` dengan paket yang berbeda sama sekali? Jalankan `php artisan migrate:fresh --seed`
(menghapus semua tabel dan data di database itu, jadi pakai database kosong).

**Batas upload:** `upload_max_filesize` dan `post_max_size` di `php.ini` harus minimal 10 MB
(bawaan PHP hanya 2 MB). Ekstensi PHP `gd` dan `mbstring` harus aktif (di Laragon biasanya sudah).

**CDN:** font, ikon, Tailwind, Alpine, DataTables, Chart.js, dan SweetAlert2 dimuat dari internet,
jadi komputer pengguna perlu terhubung internet.

## 2. Hak akses

| Fitur | Admin | Petugas |
|---|---|---|
| Login, logout, ganti password, foto profil, ubah profil | ya | ya |
| Dashboard (jumlah arsip, grafik download per hari) | semua data | data sendiri |
| Ringkasan jumlah petugas | ya | tidak |
| Kategori dan petugas: lihat | ya | ya |
| Kategori dan petugas: tambah / edit / hapus | ya | tidak |
| Arsip: lihat, preview, download | semua arsip | arsip yang ia unggah |
| Arsip: tambah | ya | ya |
| Arsip: edit, hapus | semua arsip | miliknya |
| Riwayat download | semua pengguna | miliknya |
| Pengaturan: mode terang/gelap | ya | ya |
| Pengaturan: foto latar halaman login | ya | tidak |

Admin memiliki CRUD arsip penuh (`'admin_can_input' => true` di `config/arsip.php`). Ubah menjadi `false`
jika admin hanya boleh melihat, preview, download, dan hapus.

### Pengaturan: mode terang/gelap
Menu **Pengaturan** (sidebar bagian Akun, bisa dibuka semua pengguna) memiliki switch **Mode gelap**.
Pilihannya tersimpan di browser masing-masing pengguna (tidak mengubah tampilan orang lain), berlaku juga
di halaman login, dan bisa dikembalikan ke "Ikuti pengaturan perangkat". Sebelum memilih apa pun,
tampilan mengikuti pengaturan terang/gelap perangkat.

### Pengaturan: foto latar halaman login
Di halaman Pengaturan yang sama, admin dapat mengunggah foto yang tampil di panel biru
halaman login. Foto JPG/PNG/WEBP maksimal 4 MB, ada pratinjau sebelum disimpan, dan bisa dihapus
sehingga panel kembali biru polos. Foto disimpan di `storage/app/public/pengaturan` (butuh
`php artisan storage:link`). Setelah memperbarui dari versi sebelumnya, jalankan `php artisan migrate`
untuk membuat tabel `pengaturans`.

## 3. Kolom arsip

Setiap kategori punya **jenis isian**, dipilih admin saat membuat kategori (tidak bisa diubah sesudahnya):

| Jenis isian | Dipakai oleh | Kolom |
|---|---|---|
| Formulir lengkap | PSAP dan PSPP (bawaan) | seluruh kolom Formulir Laporan B.1 (tabel di bawah) + cetak Formulir PDF |
| Sederhana | kategori baru buatan admin | judul, keterangan, tanggal, dan berkas yang dilampirkan |

Saat menekan **Tambah arsip**, petugas memilih kategori dulu, lalu form menyesuaikan jenisnya.
Arsip tidak bisa dipindah ke kategori dengan jenis isian yang berbeda.
Pada daftar "Semua arsip", kolomnya diringkas (kategori, judul/nomor, keterangan, tanggal);
saat disaring per kategori, kolomnya mengikuti jenis kategori itu.

### Kolom jenis "Formulir lengkap"

PSAP dan PSPP memakai kolom yang sama, mengikuti **Formulir Laporan (Model B.1)**:

| Bagian | Kolom |
|---|---|
| Data arsip | Kategori, nomor laporan |
| 1. Identitas pelapor | Nama, tempat/tanggal lahir, jenis kelamin, pekerjaan, kewarganegaraan, no. telp/HP, alamat, e-mail (tidak wajib) |
| 2. Identitas terlapor | Nama, no. telp/HP (tidak wajib), alamat |
| 3. Peristiwa | Peristiwa, tempat kejadian, hari/tanggal kejadian, hari/tanggal diketahui |
| 4. Saksi | Daftar saksi (nama, alamat, telp), tidak wajib, maksimal 10, lewat tombol "Tambah saksi" |
| 5. Bukti-bukti | Satu bukti per baris |
| 6. Uraian kejadian | Teks kronologis |
| Penerimaan laporan | Dilaporkan di, hari/tanggal, pukul (WIB), penerima laporan |
| Berkas | Scan formulir beserta lampiran (PDF/JPG/PNG, maks 10 MB) |

### Formulir PDF
Tombol **Formulir PDF** (di daftar arsip dan halaman detail) membuat Formulir Model B.1 otomatis dari data
yang diisi (kop logo Bawaslu, kertas F4). Tombol **Berkas scan** mengunduh file yang diunggah.
Setiap pembuatan PDF dan unduhan berkas tercatat di Riwayat download. Preview di browser tidak dicatat.

Formulir lain (B.2 Temuan, B.3, B.3.1, B.6, B.7) belum dibuatkan keluaran PDF. B.2, B.6, dan B.7 membutuhkan
isian tambahan yang belum ada di sistem.

### Mengubah kolom
1. **`config/arsip.php`** bagian `fields`: tambah/ubah/hapus entri (grup, label, tipe
   `text|email|date|time|textarea|select`, aturan validasi, tampil di tabel atau tidak).
   Form, tabel, halaman detail, dan validasi mengikuti otomatis.
2. **Migrasi**: buat migrasi baru untuk kolomnya, mis. `php artisan make:migration add_kolom_baru_to_arsips_table`.
   Jika belum ada data sungguhan, boleh ubah `create_arsips_table` lalu `php artisan migrate:fresh --seed`.

Kolom jenis sederhana ada di bagian `fields_sederhana` pada file yang sama.

Jika `nomor_laporan` diganti namanya, sesuaikan juga `label_field` dan `sort_field` di config.

## 4. Keamanan yang sudah diterapkan

- Password di-hash (bcrypt), minimal 8 karakter dengan huruf dan angka.
- Pembatasan login: 5 percobaan per menit per username + IP.
- Sesi diganti setelah login dan setelah ganti password; logout menghapus sesi dan token CSRF.
- Semua form memakai token CSRF; hapus data memakai method `DELETE`.
- Hak akses berlapis: middleware peran (halaman admin) + `ArsipPolicy` (petugas hanya menyentuh arsipnya).
- Berkas arsip disimpan di `storage/app/private` dengan nama acak, **tanpa URL publik**; hanya bisa dibuka
  lewat route yang memeriksa login dan hak akses.
- Validasi jenis dan ukuran berkas di server; nama asli berkas dibersihkan.
- Header keamanan (nosniff, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS saat HTTPS);
  halaman setelah login tidak di-cache.
- Role tidak bisa di-mass-assign; petugas tidak bisa membuat akun admin.
- Riwayat download tetap tersimpan walau arsip atau petugas kemudian dihapus.

### Sebelum dipakai di server sungguhan
- `.env`: `APP_ENV=production`, `APP_DEBUG=false`.
- Pasang HTTPS, lalu set `SESSION_SECURE_COOKIE=true` dan `SESSION_ENCRYPT=true`.
- Pakai user MySQL khusus (bukan `root`) dengan password kuat.
- Jadwalkan backup database **dan** folder `storage/app/private`.
- Opsional: ganti library CDN dengan build lokal, tambahkan Content-Security-Policy.

## 5. Deploy ke InfinityFree (hosting gratis)

Panduan langkah demi langkah ada di jawaban chat; berkas bantu tersedia di folder `deploy-infinityfree/`:
`htaccess.txt` (ganti nama jadi `.htaccess`, taruh di `htdocs`) dan `env.infinityfree.example`
(contoh `.env` produksi). Folder `deploy-infinityfree/` tidak perlu ikut diunggah ke hosting.

## 6. Struktur singkat

```
app/Http/Controllers   Auth, Dashboard, Profil, Pengaturan, Kategori, Petugas, Arsip, Riwayat
app/Http/Middleware    RoleMiddleware, SecurityHeaders
app/Models             User, Kategori, Arsip, DownloadLog, Pengaturan
app/Policies           ArsipPolicy
config/arsip.php       kolom arsip, batas upload, pengaturan hak admin
database/migrations    users (tambahan), kategoris, arsips, download_logs, pengaturans
resources/views        layouts, partials, dashboard, arsip, kategori, petugas, riwayat, profil, auth
lang/id                terjemahan pesan validasi
```

## 7. Status pengujian

Kode ditulis tanpa bisa menjalankan Laravel (tidak ada PHP di lingkungan penulis). Yang sudah dicek:
keseimbangan direktif Blade di semua view. Yang **belum** teruji: migrasi di MySQL, alur login sampai
upload di browser, pembuatan PDF, dan tampilan visual. Jika ada error, salin pesannya agar bisa diperbaiki.
