<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
            $table->string('file_name')->nullable()->change();
            $table->string('file_mime', 100)->nullable()->change();
            $table->unsignedBigInteger('file_size')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Tidak dikembalikan ke NOT NULL karena arsip tanpa lampiran mungkin sudah tersimpan.
    }
};
