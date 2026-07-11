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
        Schema::create('kwitansi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_bku_id')->constrained('transaksi_bku')->onDelete('cascade');
            $table->string('nomor', 100)->unique();
            $table->timestamp('dicetak_pada')->nullable();
            $table->string('file_pdf_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kwitansi');
    }
};
