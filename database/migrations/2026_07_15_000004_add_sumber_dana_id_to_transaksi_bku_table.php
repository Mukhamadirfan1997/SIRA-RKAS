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
            $table->foreignId('sumber_dana_id')->nullable()->after('metode_pengadaan')
                  ->constrained('sumber_dana')->onDelete('restrict');
            $table->index(['sumber_dana_id', 'bulan'], 'tb_sumber_dana_bulan_idx');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('
                UPDATE transaksi_bku tb
                JOIN rkas_item ri ON ri.id = tb.rkas_item_id
                SET tb.sumber_dana_id = ri.sumber_dana_id
                WHERE ri.sumber_dana_id IS NOT NULL
            ');
        }
    }

    public function down(): void
    {
        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->dropIndex('tb_sumber_dana_bulan_idx');
            $table->dropForeign(['sumber_dana_id']);
            $table->dropColumn('sumber_dana_id');
        });
    }
};
