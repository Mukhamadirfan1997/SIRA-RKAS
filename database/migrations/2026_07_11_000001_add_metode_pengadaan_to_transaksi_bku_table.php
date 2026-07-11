<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->enum('metode_pengadaan', ['siplah', 'non_siplah'])->nullable()->after('toko_penerima');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->dropColumn('metode_pengadaan');
        });
    }
};
