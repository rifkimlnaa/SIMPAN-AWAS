<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arsips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategoris')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            // Kolom isian mengikuti Formulir Laporan. Samakan dengan 'fields' di config/arsip.php.
            $table->string('nomor_laporan', 100);

            // 1. Identitas pelapor
            $table->string('nama_pelapor', 150);
            $table->string('ttl_pelapor', 150);
            $table->string('jenis_kelamin_pelapor', 20);
            $table->string('pekerjaan_pelapor', 100);
            $table->string('kewarganegaraan_pelapor', 100);
            $table->string('telp_pelapor', 30);
            $table->text('alamat_pelapor');
            $table->string('email_pelapor', 150)->nullable();

            // 2. Identitas terlapor
            $table->string('nama_terlapor', 150);
            $table->string('telp_terlapor', 30)->nullable();
            $table->text('alamat_terlapor');

            // 3. Peristiwa yang dilaporkan
            $table->text('peristiwa');
            $table->string('tempat_kejadian');
            $table->date('tanggal_kejadian');
            $table->date('tanggal_diketahui');

            // 4. Saksi (bisa lebih dari satu): [{nama, alamat, telp}, ...]
            $table->json('saksi')->nullable();

            // 5 & 6. Bukti dan uraian kejadian
            $table->text('bukti');
            $table->text('uraian_kejadian');

            // Penerimaan laporan
            $table->string('tempat_dilaporkan');
            $table->date('tanggal_dilaporkan');
            $table->time('waktu_dilaporkan');
            $table->string('penerima_laporan', 150);

            // Berkas tersimpan di storage privat, bukan folder publik.
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_mime', 100);
            $table->unsignedBigInteger('file_size');

            $table->timestamps();

            $table->index(['kategori_id', 'tanggal_dilaporkan']);
            $table->index('nomor_laporan');
            $table->index('nama_pelapor');
            $table->index('nama_terlapor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arsips');
    }
};
