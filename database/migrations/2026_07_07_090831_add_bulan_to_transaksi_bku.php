<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_bku', function (Blueprint $table) {
            // Tambah kolom bulan (diturunkan dari tanggal, tapi disimpan eksplisit untuk filter cepat)
            $table->tinyInteger('bulan')->unsigned()->nullable()->after('tanggal');
            // Index untuk performa filter per bulan
            $table->index(['sekolah_id', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->dropIndex(['sekolah_id', 'bulan']);
            $table->dropColumn('bulan');
        });
    }
};
