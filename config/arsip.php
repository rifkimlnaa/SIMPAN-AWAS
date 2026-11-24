<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi SIMPAN-AWAS
|--------------------------------------------------------------------------
| Struktur arsip:
|
|   Kategori UTAMA (tetap, tidak bisa dihapus):
|     1. Penanganan Pelanggaran  -> Formulir Laporan (B.1), Formulir Temuan (B.2),
|                                   Tanda Bukti Penyampaian Laporan (B.3),
|                                   Tanda Bukti Perbaikan Laporan (B.3.1)
|     2. Penyelesaian Sengketa   -> Tanda Terima Dokumen (PSPP-03), Daftar Alat Bukti Pemohon (PSPP-10),
|                                   Berita Acara Verifikasi Permohonan (PSPP-04)
|
|   Kategori TAMBAHAN (dibuat admin): hanya nama dan keterangan. Isian arsipnya sederhana
|   (judul, keterangan, tanggal, berkas) dan tanpa pilihan jenis arsip.
*/

// ---------------------------------------------------------------------------
// Potongan definisi kolom yang dipakai lebih dari satu formulir.
// ---------------------------------------------------------------------------
$telp = ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'];
$telpOpsional = ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'];

$kolomTerlapor = [
    'nama_terlapor' => [
        'group' => 'terlapor', 'label' => 'Nama', 'type' => 'text',
        'rules' => ['required', 'string', 'max:150'], 'list' => true,
    ],
    'alamat_terlapor' => [
        'group' => 'terlapor', 'label' => 'Alamat', 'type' => 'textarea', 'rows' => 2,
        'hint' => 'Jika alamat tidak lengkap/tidak diketahui, cukup tulis dusun/desa/kelurahan.',
        'rules' => ['required', 'string', 'max:500'],
    ],
    'telp_terlapor' => [
        'group' => 'terlapor', 'label' => 'No. Telp/HP', 'type' => 'text', 'hint' => 'Tidak wajib diisi.',
        'rules' => $telpOpsional,
    ],
];

$kolomPeristiwa = [
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
];

$kolomIdentitasPelapor = [
    'nama_pelapor' => [
        'group' => 'pelapor', 'label' => 'Nama', 'type' => 'text',
        'rules' => ['required', 'string', 'max:150'], 'list' => true,
    ],
    'ttl_pelapor' => [
        'group' => 'pelapor', 'label' => 'Tempat/Tanggal Lahir', 'type' => 'text',
        'rules' => ['required', 'string', 'max:150'],
        'hint' => 'Contoh: Banyumas, 17 Agustus 1990',
    ],
];

// Kolom isian untuk Tanda Bukti Penyampaian (B.3) dan Perbaikan (B.3.1): bentuknya sama.
$kolomTandaBukti = [
    'nomor_laporan' => [
        'group' => 'umum', 'label' => 'Nomor', 'type' => 'text',
        'rules' => ['required', 'string', 'max:100'], 'list' => true,
    ],
    ...$kolomIdentitasPelapor,
    'alamat_pelapor' => [
        'group' => 'pelapor', 'label' => 'Alamat', 'type' => 'textarea', 'rows' => 2,
        'rules' => ['required', 'string', 'max:500'],
    ],
    'telp_pelapor' => [
        'group' => 'pelapor', 'label' => 'No. Telp/HP', 'type' => 'text',
        'rules' => $telp,
    ],
    'tanggal_dilaporkan' => [
        'group' => 'penerimaan', 'label' => 'Hari dan Tanggal', 'type' => 'date',
        'rules' => ['required', 'date'], 'list' => true,
    ],
    'waktu_dilaporkan' => [
        'group' => 'penerimaan', 'label' => 'Waktu (WIB)', 'type' => 'time',
        'hint' => 'Format 24 jam, contoh 09:30.',
        'rules' => ['required', 'date_format:H:i'],
    ],
    'jenis_pemilu' => [
        'group' => 'penerimaan', 'label' => 'Jenis Pemilu', 'type' => 'text', 'wide' => true,
        'default' => 'DPR/DPD/DPRD Provinsi/DPRD Kabupaten/Kota atau Pemilu Presiden dan Wakil Presiden',
        'hint' => 'Terkait laporan dugaan pelanggaran Pemilu apa. Sesuaikan dengan jenis pemilunya.',
        'rules' => ['required', 'string', 'max:255'],
    ],
    'tempat_dilaporkan' => [
        'group' => 'penerimaan', 'label' => 'Tempat (untuk baris tempat, tanggal)', 'type' => 'text',
        'rules' => ['required', 'string', 'max:255'],
    ],
    'penerima_laporan' => [
        'group' => 'penerimaan', 'label' => 'Penerima Laporan', 'type' => 'text',
        'rules' => ['required', 'string', 'max:150'],
    ],
];

$barisDokumenTandaBukti = [
    'label' => 'Dokumen',
    'min' => 1, 'max' => 20,
    'tambah' => 'Tambah dokumen',
    'kosong' => 'Belum ada dokumen. Klik tombol di bawah untuk menambahkan.',
    'kolom' => [
        'bentuk' => ['label' => 'Bentuk Dokumen', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'wide' => true],
        'jumlah' => ['label' => 'Jumlah', 'type' => 'text', 'rules' => ['required', 'string', 'max:50']],
    ],
];

return [

    /*
    | Nama aplikasi. Ubah di sini saja, semua halaman ikut berganti.
    */
    'nama' => 'SIMPAN-AWAS',
    'nama_lengkap' => 'Sistem Pengarsipan & Manajemen Dokumen Pengawasan',

    /*
    | Identitas kantor pada kop formulir Penyelesaian Sengketa (Tanda Terima, Berita Acara).
    | Isi lewat .env (ARSIP_KANTOR_ALAMAT, ARSIP_KANTOR_EMAIL, ARSIP_KANTOR_WEBSITE) atau langsung di sini.
    | Baris yang dikosongkan tidak dicetak.
    */
    'kantor' => [
        'nama' => env('ARSIP_KANTOR_NAMA', 'Bawaslu Kabupaten Banyumas'),
        'alamat' => env('ARSIP_KANTOR_ALAMAT', ''),
        'email' => env('ARSIP_KANTOR_EMAIL', ''),
        'website' => env('ARSIP_KANTOR_WEBSITE', ''),
    ],

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
    | Foto bukti pada Formulir Laporan (B.1) dan Formulir Temuan (B.2).
    | Foto otomatis diperkecil saat disimpan agar PDF/Word tidak membengkak.
    */
    'foto' => [
        'maks_jumlah' => 10,          // foto per arsip
        'maks_kb' => 8192,            // ukuran tiap foto yang diunggah (8 MB)
        'mimes' => ['jpg', 'jpeg', 'png'],
        'sisi_maks_px' => 1600,       // sisi terpanjang setelah diperkecil
        'kualitas_jpeg' => 82,
    ],

    /*
    | KATEGORI UTAMA. Dibuat otomatis oleh migrasi/seeder dan tidak bisa dihapus.
    |   kunci   : nilai kolom kategoris.jenis
    |   nama    : nama awal (admin boleh mengubah nama dan keterangannya)
    */
    'kategori_utama' => [
        'pelanggaran' => [
            'nama' => 'Penanganan Pelanggaran',
            'keterangan' => 'Arsip penanganan dugaan pelanggaran pemilu: laporan, temuan, dan tanda bukti.',
            'ikon' => 'shield-alert',
        ],
        'sengketa' => [
            'nama' => 'Penyelesaian Sengketa',
            'keterangan' => 'Arsip permohonan penyelesaian sengketa proses pemilu.',
            'ikon' => 'scale',
        ],
    ],

    /*
    | JENIS ARSIP: pilihan di dalam tiap kategori utama (muncul sebagai dropdown).
    | Setiap jenis punya kolom isian sendiri dan cetakan PDF yang mengikuti formulir aslinya.
    |
    |   kategori : kunci kategori utama (pelanggaran | sengketa)
    |   kode     : nomor/kode formulir (tampil di kotak pojok kanan atas formulir)
    |   groups   : kelompok isian pada form & halaman detail (urut). Kunci khusus:
    |              'saksi'   = daftar saksi (bisa lebih dari satu)
    |              'dokumen' = tabel baris dinamis, definisinya di 'baris'
    |   fields   : kolom isian (group, label, type, rules, ...). type: text|email|date|time|textarea|select
    |              list = tampil sebagai kolom di tabel daftar arsip
    |   pdf      : view Blade cetakan formulir
    */
    'jenis_arsip' => [

        // ------------------------------------------------------------------ B.1
        'formulir_laporan' => [
            'kategori' => 'pelanggaran',
            'label' => 'Formulir Laporan',
            'kode' => 'FORMULIR MODEL B.1',
            'singkat' => 'B.1',
            'ikon' => 'file-text',
            'pdf' => 'arsip.pdf.b1',
            'pdf_nama' => 'Formulir-B1-Laporan',
            'foto_bukti' => true,
            'groups' => [
                'umum' => 'Data arsip',
                'pelapor' => 'Identitas pelapor',
                'terlapor' => 'Identitas terlapor',
                'peristiwa' => 'Peristiwa yang dilaporkan',
                'saksi' => 'Saksi-saksi',
                'bukti' => 'Bukti & uraian kejadian',
                'foto' => 'Foto bukti',
                'pelaporan' => 'Penerimaan laporan',
            ],
            'fields' => [
                'nomor_laporan' => [
                    'group' => 'umum', 'label' => 'Nomor Laporan', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:100'],
                    'hint' => 'Nomor penyampaian laporan (bertanda * pada formulir).',
                    'list' => true,
                ],

                // 1. Identitas Pelapor
                ...$kolomIdentitasPelapor,
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
                    'rules' => $telp,
                ],
                'alamat_pelapor' => [
                    'group' => 'pelapor', 'label' => 'Alamat', 'type' => 'textarea', 'rows' => 2,
                    'rules' => ['required', 'string', 'max:500'],
                ],
                'email_pelapor' => [
                    'group' => 'pelapor', 'label' => 'E-Mail', 'type' => 'email', 'hint' => 'Tidak wajib diisi.',
                    'rules' => ['nullable', 'email', 'max:150'],
                ],

                // 2. Identitas Terlapor, 3. Peristiwa
                ...$kolomTerlapor,
                ...$kolomPeristiwa,

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

                // Bagian bawah formulir
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
        ],

        // ------------------------------------------------------------------ B.2
        'formulir_temuan' => [
            'kategori' => 'pelanggaran',
            'label' => 'Formulir Temuan',
            'kode' => 'FORMULIR MODEL B.2',
            'singkat' => 'B.2',
            'ikon' => 'search-check',
            'pdf' => 'arsip.pdf.b2',
            'pdf_nama' => 'Formulir-B2-Temuan',
            'foto_bukti' => true,
            'groups' => [
                'umum' => 'Data arsip',
                'pengawas' => 'Data pengawas yang menemukan',
                'terlapor' => 'Identitas terlapor',
                'peristiwa' => 'Peristiwa yang ditemukan',
                'saksi' => 'Saksi-saksi',
                'bukti' => 'Bukti & uraian singkat kejadian',
                'foto' => 'Foto bukti',
                'penutup' => 'Tempat, tanggal, dan penandatangan',
            ],
            'fields' => [
                'nomor_laporan' => [
                    'group' => 'umum', 'label' => 'Nomor Temuan', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:100'],
                    'hint' => 'Nomor register Temuan (bertanda * pada formulir).',
                    'list' => true,
                ],
                'nama_pengawas' => [
                    'group' => 'pengawas', 'label' => 'Nama', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:150'], 'list' => true,
                    'hint' => 'Nama Pengawas Pemilu yang menemukan dugaan pelanggaran.',
                ],
                'jabatan_pengawas' => [
                    'group' => 'pengawas', 'label' => 'Jabatan', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:150'],
                ],
                'alamat_pengawas' => [
                    'group' => 'pengawas', 'label' => 'Alamat', 'type' => 'textarea', 'rows' => 2,
                    'rules' => ['required', 'string', 'max:500'],
                ],
                ...$kolomTerlapor,
                ...$kolomPeristiwa,
                'nomor_surat_tugas' => [
                    'group' => 'bukti', 'label' => 'Surat Tugas Pengawasan Nomor', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:150'],
                ],
                'nomor_lhp' => [
                    'group' => 'bukti', 'label' => 'Laporan Hasil Pengawasan Nomor', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:150'],
                ],
                'bukti' => [
                    'group' => 'bukti', 'label' => 'Bukti lainnya', 'type' => 'textarea', 'rows' => 3,
                    'hint' => 'Tidak wajib. Tulis satu bukti per baris (menjadi butir c, d, dst).',
                    'rules' => ['nullable', 'string', 'max:3000'],
                ],
                'uraian_kejadian' => [
                    'group' => 'bukti', 'label' => 'Uraian Singkat Kejadian', 'type' => 'textarea', 'rows' => 6,
                    'hint' => 'Kronologis: peristiwa apa yang dianggap sebagai dugaan pelanggaran pemilu, di mana, kapan, dan bagaimana kejadiannya.',
                    'rules' => ['required', 'string', 'max:10000'],
                ],
                'tempat_dilaporkan' => [
                    'group' => 'penutup', 'label' => 'Tempat', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:255'],
                ],
                'tanggal_dilaporkan' => [
                    'group' => 'penutup', 'label' => 'Tanggal', 'type' => 'date',
                    'rules' => ['required', 'date'], 'list' => true,
                ],
                'lembaga' => [
                    'group' => 'penutup', 'label' => 'Nama Lembaga', 'type' => 'text', 'wide' => true,
                    'default' => 'Bawaslu Kabupaten Banyumas',
                    'hint' => 'Diisi sesuai dengan nama lembaganya (bertanda ***** pada formulir).',
                    'rules' => ['required', 'string', 'max:150'],
                ],
                'nama_penandatangan' => [
                    'group' => 'penutup', 'label' => 'Nama Penandatangan', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:150'],
                ],
                'jabatan_penandatangan' => [
                    'group' => 'penutup', 'label' => 'Jabatan', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:150'],
                ],
            ],
        ],

        // ------------------------------------------------------------------ B.3
        'bukti_penyampaian' => [
            'kategori' => 'pelanggaran',
            'label' => 'Tanda Bukti Penyampaian Laporan',
            'kode' => 'FORMULIR MODEL B.3',
            'singkat' => 'B.3',
            'ikon' => 'file-check',
            'pdf' => 'arsip.pdf.b3',
            'pdf_nama' => 'Formulir-B3-Tanda-Bukti-Penyampaian-Laporan',
            'groups' => [
                'umum' => 'Data arsip',
                'pelapor' => 'Telah diterima dari',
                'dokumen' => 'Dokumen',
                'penerimaan' => 'Penerimaan',
            ],
            'baris' => $barisDokumenTandaBukti,
            'fields' => $kolomTandaBukti,
        ],

        // ------------------------------------------------------------------ B.3.1
        'bukti_perbaikan' => [
            'kategori' => 'pelanggaran',
            'label' => 'Tanda Bukti Perbaikan Laporan',
            'kode' => 'FORMULIR MODEL B.3.1',
            'singkat' => 'B.3.1',
            'ikon' => 'file-pen-line',
            'pdf' => 'arsip.pdf.b31',
            'pdf_nama' => 'Formulir-B31-Tanda-Bukti-Perbaikan-Laporan',
            'groups' => [
                'umum' => 'Data arsip',
                'pelapor' => 'Telah diterima dari',
                'dokumen' => 'Dokumen',
                'penerimaan' => 'Penerimaan',
            ],
            'baris' => $barisDokumenTandaBukti,
            'fields' => array_replace_recursive($kolomTandaBukti, [
                'nomor_laporan' => ['hint' => 'Diberi nomor yang sama dengan Tanda Bukti Penyampaian Laporan.'],
            ]),
        ],

        // ------------------------------------------------------------------ Tanda Terima (PSPP-03)
        'tanda_terima' => [
            'kategori' => 'sengketa',
            'label' => 'Tanda Terima Dokumen',
            'kode' => 'FORMULIR MODEL PSPP-03',
            'kode_judul' => 'TANDA TERIMA DOKUMEN',
            'ikon' => 'file-input',
            'pdf' => 'arsip.pdf.tanda_terima',
            'pdf_nama' => 'Tanda-Terima-Dokumen',
            'groups' => [
                'umum' => 'Data arsip',
                'penerimaan' => 'Penerimaan dokumen',
                'pemohon' => 'Pemohon',
                'dokumen' => 'Rincian dokumen',
            ],
            'baris' => [
                'label' => 'Rincian dokumen',
                'min' => 1, 'max' => 30,
                'tambah' => 'Tambah dokumen lainnya',
                'kosong' => 'Belum ada dokumen dipilih. Centang dokumen di atas untuk menambahkannya.',
                'pilihan' => [
                    'permohonan' => 'Surat permohonan penyelesaian sengketa proses Pemilu',
                    'identitas' => 'Fotokopi identitas pemohon',
                    'kuasa' => 'Surat kuasa (jika dikuasakan)',
                    'keputusan_kpu' => 'Salinan keputusan KPU yang disengketakan',
                    'uraian_alasan' => 'Uraian alasan permohonan',
                    'alat_bukti' => 'Alat bukti pendukung (dokumen, surat, rekaman, atau bukti elektronik lainnya)',
                ],
                'kolom' => [
                    'jenis' => [
                        'label' => 'Jenis Dokumen', 'type' => 'text', 'wide' => true,
                        'hint' => 'Pilih dari daftar dokumen. Gunakan "Tambah dokumen lainnya" untuk dokumen di luar daftar.',
                        'rules' => ['required', 'string', 'max:500'],
                    ],
                    'cetak' => ['label' => 'Jumlah Dokumen Cetak', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:9999']],
                    'digital' => ['label' => 'Jumlah Dokumen Digital', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:9999']],
                    'keterangan' => [
                        'label' => 'Keterangan', 'type' => 'textarea', 'rows' => 2, 'wide' => true,
                        'hint' => 'Opsional. Isi bila ada keterangan khusus untuk dokumen ini.',
                        'rules' => ['nullable', 'string', 'max:2000'],
                    ],
                ],
            ],
            'fields' => [
                'nomor_laporan' => [
                    'group' => 'umum', 'label' => 'Nomor Tanda Terima', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:100'], 'list' => true,
                    'hint' => 'Contoh: 001/PS.PNM.LG/91/IV/2023',
                ],
                'tanggal_dilaporkan' => [
                    'group' => 'penerimaan', 'label' => 'Hari dan Tanggal Penerimaan', 'type' => 'date',
                    'rules' => ['required', 'date'], 'list' => true,
                    'hint' => 'Hari, tanggal, bulan, dan tahun pada kalimat pembuka dicetak otomatis dari tanggal ini.',
                ],
                'waktu_dilaporkan' => [
                    'group' => 'penerimaan', 'label' => 'Pukul (WIB)', 'type' => 'time',
                    'hint' => 'Format 24 jam, contoh 11:59.',
                    'rules' => ['required', 'date_format:H:i'],
                ],
                'nama_pemohon' => [
                    'group' => 'pemohon', 'label' => 'Nama Pemohon', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:150'], 'list' => true,
                ],
                'nama_penyerah' => [
                    'group' => 'pemohon', 'label' => 'Yang Menyerahkan', 'type' => 'text',
                    'hint' => 'Nama yang menyerahkan dokumen (bisa sama dengan Pemohon).',
                    'rules' => ['required', 'string', 'max:150'],
                ],
                'penerima_laporan' => [
                    'group' => 'pemohon', 'label' => 'Petugas Penerima', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:150'],
                ],
            ],
        ],

        // ------------------------------------------------------------------ Daftar Alat Bukti (PSPP-10)
        'alat_bukti' => [
            'kategori' => 'sengketa',
            'label' => 'Daftar Alat Bukti Pemohon',
            'kode' => 'FORMULIR MODEL PSPP-10',
            'kode_judul' => 'DAFTAR ALAT BUKTI',
            'ikon' => 'list-checks',
            'pdf' => 'arsip.pdf.alat_bukti',
            'pdf_nama' => 'Daftar-Alat-Bukti-Pemohon',
            'groups' => [
                'umum' => 'Data arsip',
                'tujuan' => 'Kepada Yth.',
                'dokumen' => 'Alat bukti',
                'penutup' => 'Tempat, tanggal, dan penandatangan',
            ],
            'baris' => [
                'label' => 'Alat bukti',
                'min' => 1, 'max' => 50,
                'tambah' => 'Tambah alat bukti',
                'kosong' => 'Belum ada alat bukti. Klik tombol di bawah untuk menambahkan.',
                'butir' => 'Alat bukti',
                'nomor_awalan' => 'P-',
                'kolom' => [
                    'nama' => ['label' => 'Nama Alat Bukti', 'type' => 'text', 'wide' => true, 'rules' => ['required', 'string', 'max:500']],
                    'keterangan' => ['label' => 'Keterangan', 'type' => 'text', 'wide' => true, 'rules' => ['nullable', 'string', 'max:500']],
                ],
            ],
            'fields' => [
                'nama_pemohon' => [
                    'group' => 'umum', 'label' => 'Nama Pemohon', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:150'], 'list' => true,
                    'hint' => 'Dicetak di bawah "Hormat kami" sebagai tanda tangan dan nama jelas.',
                ],
                'nomor_laporan' => [
                    'group' => 'umum', 'label' => 'Nomor Register Permohonan', 'type' => 'text',
                    'rules' => ['nullable', 'string', 'max:100'], 'list' => true,
                    'hint' => 'Tidak wajib. Hanya untuk pencarian arsip, tidak dicetak pada formulir.',
                ],
                'tujuan_instansi' => [
                    'group' => 'tujuan', 'label' => 'Ketua Bawaslu', 'type' => 'text', 'wide' => true,
                    'default' => 'Bawaslu Kabupaten Banyumas',
                    'hint' => 'Tingkatan wilayah dan nama daerah, contoh: Bawaslu Kabupaten Banyumas.',
                    'rules' => ['required', 'string', 'max:150'],
                ],
                'tujuan_lokasi' => [
                    'group' => 'tujuan', 'label' => 'Di (kota/tempat)', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:150'],
                ],
                'tempat_dilaporkan' => [
                    'group' => 'penutup', 'label' => 'Tempat Penyusunan', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:255'],
                ],
                'tanggal_dilaporkan' => [
                    'group' => 'penutup', 'label' => 'Tanggal Penyusunan', 'type' => 'date',
                    'rules' => ['required', 'date'], 'list' => true,
                ],
            ],
        ],

        // ------------------------------------------------------------------ Berita Acara Verifikasi (PSPP-04)
        'ba_verifikasi' => [
            'kategori' => 'sengketa',
            'label' => 'Berita Acara Verifikasi Permohonan',
            'kode' => 'FORMULIR MODEL PSPP-04',
            'kode_judul' => 'BERITA ACARA VERIFIKASI PERMOHONAN PENYELESAIAN SENGKETA PROSES PEMILU',
            'ikon' => 'clipboard-check',
            'pdf' => 'arsip.pdf.ba_verifikasi',
            'pdf_nama' => 'Berita-Acara-Verifikasi-Permohonan',
            'groups' => [
                'umum' => 'Data arsip',
                'verifikasi' => 'Pelaksanaan verifikasi',
                'dokumen' => 'Hasil pemeriksaan dokumen',
                'penutup' => 'Kesimpulan dan penandatangan',
            ],
            'baris' => [
                'label' => 'Hasil pemeriksaan dokumen',
                'min' => 1, 'max' => 30,
                'tambah' => 'Tambah dokumen',
                'kosong' => 'Belum ada dokumen. Klik tombol di bawah untuk menambahkan.',
                'kolom' => [
                    'jenis' => [
                        'label' => 'Jenis Dokumen', 'type' => 'textarea', 'rows' => 3, 'wide' => true,
                        'hint' => 'Baris pertama = nama dokumen. Baris berikutnya (tekan Enter) otomatis menjadi butir a, b, c, dst.',
                        'rules' => ['required', 'string', 'max:2000'],
                    ],
                    'cetak' => ['label' => 'Jumlah Dokumen Cetak', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:30']],
                    'digital' => ['label' => 'Jumlah Dokumen Digital', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:30']],
                    'hasil' => ['label' => 'Hasil Pemeriksaan', 'type' => 'text', 'default' => 'Lengkap', 'rules' => ['required', 'string', 'max:100']],
                ],
            ],
            'fields' => [
                'nomor_laporan' => [
                    'group' => 'umum', 'label' => 'Nomor Tanda Terima', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:100'], 'list' => true,
                    'hint' => 'Nomor Tanda Terima Dokumen yang diverifikasi.',
                ],
                'lembaga' => [
                    'group' => 'umum', 'label' => 'Nama Lembaga', 'type' => 'text', 'wide' => true,
                    'default' => 'Bawaslu Kabupaten Banyumas',
                    'rules' => ['required', 'string', 'max:150'],
                ],
                'tanggal_dilaporkan' => [
                    'group' => 'verifikasi', 'label' => 'Hari dan Tanggal Verifikasi', 'type' => 'date',
                    'rules' => ['required', 'date'], 'list' => true,
                ],
                'waktu_dilaporkan' => [
                    'group' => 'verifikasi', 'label' => 'Pukul (WIB)', 'type' => 'time',
                    'hint' => 'Format 24 jam, contoh 14:35.',
                    'rules' => ['required', 'date_format:H:i'],
                ],
                'hasil_verifikasi' => [
                    'group' => 'penutup', 'label' => 'Kesimpulan Verifikasi', 'type' => 'select',
                    'options' => ['Lengkap', 'Tidak lengkap'],
                    'rules' => ['required', 'in:Lengkap,Tidak lengkap'], 'list' => true,
                ],
                'nama_penandatangan' => [
                    'group' => 'penutup', 'label' => 'Nama Ketua', 'type' => 'text',
                    'rules' => ['required', 'string', 'max:150'],
                ],
            ],
        ],
    ],

    /*
    | KATEGORI TAMBAHAN (buatan admin, selain dua kategori utama): isian sederhana.
    | Isiannya hanya judul, keterangan, tanggal, dan berkas yang dilampirkan.
    */
    'sederhana' => [
        'label' => 'Arsip',
        'groups' => [
            'umum' => 'Data arsip',
        ],
        'fields' => [
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
    ],
];
