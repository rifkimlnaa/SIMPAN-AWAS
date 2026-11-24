<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Foto bukti (Formulir Laporan B.1 dan Formulir Temuan B.2): beberapa foto per arsip.
        Schema::create('arsip_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arsip_id')->constrained('arsips')->cascadeOnDelete();
            $table->string('path');
            $table->string('nama_asli', 200)->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('ukuran')->default(0);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arsip_fotos');
    }
};
