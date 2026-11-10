<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Jenis isian per kategori: 'formulir' (Formulir Laporan lengkap) atau 'sederhana'.
        Schema::table('kategoris', function (Blueprint $table) {
            $table->string('jenis', 20)->default('sederhana')->after('keterangan');
        });

        // PSAP dan PSPP (jika sudah ada) memakai formulir lengkap.
        DB::table('kategoris')->whereIn('slug', ['psap', 'pspp'])->update(['jenis' => 'formulir']);

        // 2. Kolom untuk arsip sederhana: judul, keterangan, tanggal.
        Schema::table('arsips', function (Blueprint $table) {
            $table->string('judul', 200)->nullable()->after('kategori_id');
            $table->text('keterangan')->nullable()->after('judul');
            $table->date('tanggal_arsip')->nullable()->after('keterangan');
        });

        // 3. Kolom formulir boleh kosong, karena arsip sederhana tidak mengisinya.
        //    (Kelengkapan untuk PSAP/PSPP tetap diwajibkan oleh validasi di aplikasi.)
        Schema::table('arsips', function (Blueprint $table) {
            $table->string('nomor_laporan', 100)->nullable()->change();
            $table->string('nama_pelapor', 150)->nullable()->change();
            $table->string('ttl_pelapor', 150)->nullable()->change();
            $table->string('jenis_kelamin_pelapor', 20)->nullable()->change();
            $table->string('pekerjaan_pelapor', 100)->nullable()->change();
            $table->string('kewarganegaraan_pelapor', 100)->nullable()->change();
            $table->string('telp_pelapor', 30)->nullable()->change();
            $table->text('alamat_pelapor')->nullable()->change();
            $table->string('nama_terlapor', 150)->nullable()->change();
            $table->text('alamat_terlapor')->nullable()->change();
            $table->text('peristiwa')->nullable()->change();
            $table->string('tempat_kejadian')->nullable()->change();
            $table->date('tanggal_kejadian')->nullable()->change();
            $table->date('tanggal_diketahui')->nullable()->change();
            $table->text('bukti')->nullable()->change();
            $table->text('uraian_kejadian')->nullable()->change();
            $table->string('tempat_dilaporkan')->nullable()->change();
            $table->date('tanggal_dilaporkan')->nullable()->change();
            $table->time('waktu_dilaporkan')->nullable()->change();
            $table->string('penerima_laporan', 150)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->dropColumn(['judul', 'keterangan', 'tanggal_arsip']);
        });

        Schema::table('kategoris', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
    }
};
