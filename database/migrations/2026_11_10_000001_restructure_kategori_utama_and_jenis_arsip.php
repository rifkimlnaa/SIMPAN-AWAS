<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 | Perubahan struktur arsip:
 |  - Dua kategori utama menjadi "Penanganan Pelanggaran" dan "Penyelesaian Sengketa"
 |    (menggantikan PSAP/PSPP yang keliru).
 |  - Tiap arsip punya "jenis arsip" (B.1, B.2, B.3, B.3.1, Tanda Terima, Daftar Alat Bukti, Berita Acara).
 |  - Kolom baru untuk isian formulir B.2, B.3, B.3.1 dan formulir Penyelesaian Sengketa.
 |
 | Data lama aman: arsip PSAP/PSPP (isiannya Formulir Laporan B.1) dipindahkan ke
 | "Penanganan Pelanggaran" dengan jenis "Formulir Laporan"; arsip kategori buatan admin tetap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->string('jenis_arsip', 40)->nullable()->after('kategori_id')->index();
            $table->json('dokumen')->nullable()->after('saksi');

            // B.2 Formulir Temuan
            $table->string('nama_pengawas', 150)->nullable();
            $table->string('jabatan_pengawas', 150)->nullable();
            $table->text('alamat_pengawas')->nullable();
            $table->string('nomor_surat_tugas', 150)->nullable();
            $table->string('nomor_lhp', 150)->nullable();

            // Penutup formulir (B.2, Berita Acara)
            $table->string('lembaga', 150)->nullable();
            $table->string('nama_penandatangan', 150)->nullable();
            $table->string('jabatan_penandatangan', 150)->nullable();

            // B.3 / B.3.1
            $table->string('jenis_pemilu', 255)->nullable();

            // Penyelesaian Sengketa
            $table->string('nama_pemohon', 150)->nullable();
            $table->string('nama_penyerah', 150)->nullable();
            $table->string('tujuan_instansi', 150)->nullable();
            $table->string('tujuan_lokasi', 150)->nullable();
            $table->string('hasil_verifikasi', 30)->nullable();
        });

        $now = now();

        // 1. Pastikan kedua kategori utama ada.
        $utama = [
            'pelanggaran' => ['Penanganan Pelanggaran', 'penanganan-pelanggaran', 'Arsip penanganan dugaan pelanggaran pemilu: laporan, temuan, dan tanda bukti.'],
            'sengketa' => ['Penyelesaian Sengketa', 'penyelesaian-sengketa', 'Arsip permohonan penyelesaian sengketa proses pemilu.'],
        ];

        $idUtama = [];
        foreach ($utama as $jenis => [$nama, $slug, $keterangan]) {
            $ada = DB::table('kategoris')->where('jenis', $jenis)->first()
                ?? DB::table('kategoris')->where('slug', $slug)->first();

            if ($ada) {
                DB::table('kategoris')->where('id', $ada->id)->update(['jenis' => $jenis, 'updated_at' => $now]);
                $idUtama[$jenis] = $ada->id;
            } else {
                $idUtama[$jenis] = DB::table('kategoris')->insertGetId([
                    'nama' => $nama, 'slug' => $slug, 'keterangan' => $keterangan,
                    'jenis' => $jenis, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // 2. Arsip dari kategori lama berjenis "formulir" (PSAP/PSPP) -> Penanganan Pelanggaran / Formulir Laporan (B.1).
        $kategoriLama = DB::table('kategoris')->where('jenis', 'formulir')->pluck('id')->all();

        if ($kategoriLama) {
            DB::table('arsips')->whereIn('kategori_id', $kategoriLama)->update([
                'kategori_id' => $idUtama['pelanggaran'],
                'jenis_arsip' => 'formulir_laporan',
            ]);

            // Kategori lama sudah kosong (arsipnya dipindah), aman dihapus.
            DB::table('kategoris')->whereIn('id', $kategoriLama)->delete();
        }

        // 3. Arsip lain (kategori buatan admin) memakai isian sederhana.
        DB::table('arsips')->whereNull('jenis_arsip')->update(['jenis_arsip' => 'sederhana']);
    }

    public function down(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->dropIndex(['jenis_arsip']);
            $table->dropColumn([
                'jenis_arsip', 'dokumen',
                'nama_pengawas', 'jabatan_pengawas', 'alamat_pengawas', 'nomor_surat_tugas', 'nomor_lhp',
                'lembaga', 'nama_penandatangan', 'jabatan_penandatangan',
                'jenis_pemilu',
                'nama_pemohon', 'nama_penyerah', 'tujuan_instansi', 'tujuan_lokasi', 'hasil_verifikasi',
            ]);
        });
    }
};
