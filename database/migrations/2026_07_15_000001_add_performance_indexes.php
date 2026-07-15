<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rkas_item', function (Blueprint $table) {
            $table->index(['sekolah_id', 'tahun_anggaran_id', 'no_urut'], 'rkas_item_sekolah_tahun_urut_idx');
        });

        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->index(['sekolah_id', 'jenis', 'bulan'], 'transaksi_bku_sekolah_jenis_bulan_idx');
            $table->index(['sekolah_id', 'rkas_item_id', 'jenis', 'bulan'], 'transaksi_bku_sekolah_item_jenis_bulan_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rkas_item', function (Blueprint $table) {
            $table->dropIndex('rkas_item_sekolah_tahun_urut_idx');
        });

        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->dropIndex('transaksi_bku_sekolah_jenis_bulan_idx');
            $table->dropIndex('transaksi_bku_sekolah_item_jenis_bulan_idx');
        });
    }
};
