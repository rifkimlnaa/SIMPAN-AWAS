<?php

return [

    /*
    | Nama aplikasi. Ubah di sini saja, semua halaman ikut berganti.
    */
    'nama' => 'SIMPAN-AWAS',
    'nama_lengkap' => 'Sistem Pengarsipan & Manajemen Dokumen Pengawasan',

    /*
    | Zona waktu aplikasi (Laravel 12 menetapkan UTC secara tetap di config/app.php,
    | jadi nilai ini dipakai AppServiceProvider agar tanggal/jam sesuai WIB).
    */
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),

    /*
    | Batas dan jenis berkas arsip.
    | Pastikan upload_max_filesize & post_max_size di php.ini >= nilai ini.
    */
    'max_upload_kb' => (int) env('ARSIP_MAX_UPLOAD_KB', 10240), // bawaan 10 MB; ubah lewat .env bila hosting membatasi
    'allowed_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],

    /*
    | Apakah admin boleh menambah/mengubah arsip?
    | true  = admin punya CRUD penuh: tambah arsip, serta lihat, edit, hapus, dan unduh arsip siapa pun.
    | false = admin hanya melihat, preview, unduh, dan hapus (tidak bisa tambah/edit).
    */
    'admin_can_input' => true,

    /*
    | Kolom yang dipakai sebagai label arsip (riwayat download, judul halaman detail)
    | dan kolom untuk mengurutkan daftar arsip (terbaru di atas).
    */
    'label_field' => 'nomor_laporan',
    'sort_field' => 'tanggal_dilaporkan',

    /*
    | Kelompok isian pada form & halaman detail, sesuai urutan pada Formulir Laporan.
    | Kelompok "saksi" (bisa lebih dari satu) disisipkan setelah kelompok "peristiwa".
    */
    'groups' => [
        'umum' => 'Data arsip',
        'pelapor' => 'Identitas pelapor',
        'terlapor' => 'Identitas terlapor',
        'peristiwa' => 'Peristiwa yang dilaporkan',
        'bukti' => 'Bukti & uraian kejadian',
        'pelaporan' => 'Penerimaan laporan',
    ],

    /*
    | KOLOM ISIAN ARSIP (PSAP dan PSPP memakai kolom yang sama), mengikuti Formulir Laporan.
    | Menambah/mengubah kolom: edit daftar ini + buat migrasi untuk kolomnya (lihat README).
    |
    |   group : salah satu kunci di 'groups'
    |   type  : text | email | date | time | textarea | select
    |   list  : tampil sebagai kolom di tabel daftar arsip
    |   Opsional: options (untuk select), default, hint, rows (textarea), wide (lebar penuh)
    */
    'fields' => [
        'nomor_laporan' => [
            'group' => 'umum', 'label' => 'Nomor Laporan', 'type' => 'text',
            'rules' => ['required', 'string', 'max:100'],
            'hint' => 'Nomor penyampaian laporan (bertanda * pada formulir).',
            'list' => true,
        ],

        // 1. Identitas Pelapor
        'nama_pelapor' => [
            'group' => 'pelapor', 'label' => 'Nama', 'type' => 'text',
            'rules' => ['required', 'string', 'max:150'], 'list' => true,
        ],
        'ttl_pelapor' => [
            'group' => 'pelapor', 'label' => 'Tempat/Tanggal Lahir', 'type' => 'text',
            'rules' => ['required', 'string', 'max:150'],
            'hint' => 'Contoh: Banyumas, 17 Agustus 1990',
        ],
        'jenis_kelamin_pelapor' => [
            'group' => 'pelapor', 'label' => 'Jenis Kelamin', 'type' => 'select',
            'options' => ['Laki-laki', 'Perempuan'],
            'rules' => ['required', 'in:Laki-laki,Perempuan'],
        ],
        'pekerjaan_pelapor' => [
            'group' => 'pelapor', 'label' => 'Pekerjaan', 'type' => 'text',
            'rules' => ['required', 'string', 'max:100'],
        ],
        'kewarganegaraan_pelapor' => [
            'group' => 'pelapor', 'label' => 'Kewarganegaraan', 'type' => 'text', 'default' => 'Indonesia',
            'rules' => ['required', 'string', 'max:100'],
        ],
        'telp_pelapor' => [
            'group' => 'pelapor', 'label' => 'No. Telp/HP', 'type' => 'text',
            'rules' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
        ],
        'alamat_pelapor' => [
            'group' => 'pelapor', 'label' => 'Alamat', 'type' => 'textarea', 'rows' => 2,
            'rules' => ['required', 'string', 'max:500'],
        ],
        'email_pelapor' => [
            'group' => 'pelapor', 'label' => 'E-Mail', 'type' => 'email', 'hint' => 'Tidak wajib diisi.',
            'rules' => ['nullable', 'email', 'max:150'],
        ],

        // 2. Identitas Terlapor
        'nama_terlapor' => [
            'group' => 'terlapor', 'label' => 'Nama', 'type' => 'text',
            'rules' => ['required', 'string', 'max:150'], 'list' => true,
        ],
        'telp_terlapor' => [
            'group' => 'terlapor', 'label' => 'No. Telp/HP', 'type' => 'text', 'hint' => 'Tidak wajib diisi.',
            'rules' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
        ],
        'alamat_terlapor' => [
            'group' => 'terlapor', 'label' => 'Alamat', 'type' => 'textarea', 'rows' => 2,
            'hint' => 'Jika alamat tidak lengkap/tidak diketahui, cukup tulis dusun/desa/kelurahan.',
            'rules' => ['required', 'string', 'max:500'],
        ],

        // 3. Peristiwa yang dilaporkan
        'peristiwa' => [
            'group' => 'peristiwa', 'label' => 'Peristiwa', 'type' => 'textarea', 'rows' => 2,
            'rules' => ['required', 'string', 'max:1000'], 'list' => true,
        ],
        'tempat_kejadian' => [
            'group' => 'peristiwa', 'label' => 'Tempat Kejadian', 'type' => 'text', 'wide' => true,
            'rules' => ['required', 'string', 'max:255'],
        ],
        'tanggal_kejadian' => [
            'group' => 'peristiwa', 'label' => 'Hari dan Tanggal Kejadian', 'type' => 'date',
            'rules' => ['required', 'date'],
        ],
        'tanggal_diketahui' => [
            'group' => 'peristiwa', 'label' => 'Hari dan Tanggal Diketahui', 'type' => 'date',
            'rules' => ['required', 'date'],
        ],

        // 5 & 6. Bukti dan uraian kejadian (4. Saksi ditangani terpisah)
        'bukti' => [
            'group' => 'bukti', 'label' => 'Bukti-bukti', 'type' => 'textarea', 'rows' => 3,
            'hint' => 'Tulis satu bukti per baris.',
            'rules' => ['required', 'string', 'max:3000'],
        ],
        'uraian_kejadian' => [
            'group' => 'bukti', 'label' => 'Uraian Kejadian', 'type' => 'textarea', 'rows' => 6,
            'hint' => 'Kronologis: peristiwa apa, di mana, kapan, dan bagaimana kejadiannya.',
            'rules' => ['required', 'string', 'max:10000'],
        ],

        // Bagian bawah formulir: dilaporkan di / hari-tanggal / pukul / penerima
        'tempat_dilaporkan' => [
            'group' => 'pelaporan', 'label' => 'Dilaporkan di', 'type' => 'text',
            'rules' => ['required', 'string', 'max:255'],
        ],
        'tanggal_dilaporkan' => [
            'group' => 'pelaporan', 'label' => 'Hari dan Tanggal Dilaporkan', 'type' => 'date',
            'rules' => ['required', 'date'], 'list' => true,
        ],
        'waktu_dilaporkan' => [
            'group' => 'pelaporan', 'label' => 'Pukul (WIB)', 'type' => 'time',
            'hint' => 'Format 24 jam, contoh 09:30.',
            'rules' => ['required', 'date_format:H:i'],
        ],
        'penerima_laporan' => [
            'group' => 'pelaporan', 'label' => 'Penerima Laporan', 'type' => 'text',
            'rules' => ['required', 'string', 'max:150'],
        ],
    ],

    /*
    | KATEGORI BERJENIS "SEDERHANA" (kategori baru buatan admin, selain PSAP dan PSPP).
    | Isiannya hanya judul, keterangan, tanggal, dan berkas yang dilampirkan.
    | Untuk menambah kolom, tambahkan entri di sini dan buat migrasi untuk kolomnya.
    */
    'groups_sederhana' => [
        'umum' => 'Data arsip',
    ],

    'fields_sederhana' => [
        'judul' => [
            'group' => 'umum', 'label' => 'Judul', 'type' => 'text', 'wide' => true,
            'rules' => ['required', 'string', 'max:200'], 'list' => true,
        ],
        'keterangan' => [
            'group' => 'umum', 'label' => 'Keterangan', 'type' => 'textarea', 'rows' => 4,
            'rules' => ['nullable', 'string', 'max:2000'], 'list' => true,
        ],
        'tanggal_arsip' => [
            'group' => 'umum', 'label' => 'Tanggal', 'type' => 'date',
            'rules' => ['required', 'date'], 'list' => true,
        ],
    ],
];
