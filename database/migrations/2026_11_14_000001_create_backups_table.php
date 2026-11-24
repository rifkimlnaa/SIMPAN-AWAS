<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('type', 24)->default('manual')->index();
            $table->string('status', 24)->default('running')->index();
            $table->string('disk', 64);
            $table->string('path', 500)->nullable();
            $table->string('filename', 255);
            $table->unsignedBigInteger('size')->default(0);
            $table->char('checksum', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('restored_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('error_message', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
