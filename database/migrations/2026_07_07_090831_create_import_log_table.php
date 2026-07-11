<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('profil_sekolah')->onDelete('cascade');
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->onDelete('cascade');
            $table->tinyInteger('bulan')->unsigned(); // 1–12
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending');
            $table->integer('total_baris')->default(0);
            $table->integer('baris_berhasil')->default(0);
            $table->integer('baris_gagal')->default(0);
            $table->json('error_detail')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_log');
    }
};
