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
        Schema::create('transaksi_bku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkas_item_id')->nullable()->constrained('rkas_item')->onDelete('set null');
            $table->date('tanggal');
            $table->string('no_bukti', 100)->unique();
            $table->enum('jenis', ['penerimaan', 'pengeluaran']);
            $table->decimal('jumlah', 15, 2);
            $table->string('toko_penerima', 255)->nullable();
            $table->text('uraian')->nullable();
            $table->integer('tahap')->default(1);
            $table->boolean('status_lunas')->default(true);
            $table->decimal('saldo_berjalan', 15, 2)->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_bku');
    }
};
