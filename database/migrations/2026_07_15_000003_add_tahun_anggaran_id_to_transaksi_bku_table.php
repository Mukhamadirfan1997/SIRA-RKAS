<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->foreignId('tahun_anggaran_id')->nullable()->after('sekolah_id')
                  ->constrained('tahun_anggaran')->onDelete('restrict');
            $table->index(['sekolah_id', 'tahun_anggaran_id', 'bulan'], 'tb_sekolah_tahun_bulan_idx');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('
                UPDATE transaksi_bku tb
                JOIN rkas_item ri ON ri.id = tb.rkas_item_id
                SET tb.tahun_anggaran_id = ri.tahun_anggaran_id
                WHERE tb.rkas_item_id IS NOT NULL
            ');

            DB::statement('
                UPDATE transaksi_bku tb
                JOIN tahun_anggaran ta ON YEAR(tb.tanggal) = ta.tahun
                SET tb.tahun_anggaran_id = ta.id
                WHERE tb.tahun_anggaran_id IS NULL
            ');

            DB::statement('ALTER TABLE transaksi_bku MODIFY COLUMN tahun_anggaran_id BIGINT UNSIGNED NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->dropIndex('tb_sekolah_tahun_bulan_idx');
            $table->dropForeign(['tahun_anggaran_id']);
            $table->dropColumn('tahun_anggaran_id');
        });
    }
};
