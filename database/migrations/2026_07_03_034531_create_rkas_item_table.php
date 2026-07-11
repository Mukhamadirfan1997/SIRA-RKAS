<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rkas_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->onDelete('cascade');
            $table->integer('no_urut')->nullable();
            $table->text('uraian');
            $table->foreignId('program_id')->nullable()->constrained('master_program')->onDelete('set null');
            $table->foreignId('kode_rekening_id')->nullable()->constrained('master_kode_rekening')->onDelete('set null');
            $table->decimal('volume', 15, 2)->nullable();
            $table->string('satuan', 50)->nullable();
            $table->decimal('tarif', 15, 2)->nullable();
            $table->decimal('jumlah', 15, 2);
            $table->decimal('rencana_tahap1', 15, 2)->default(0);
            $table->decimal('rencana_tahap2', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rkas_item');
    }
};
