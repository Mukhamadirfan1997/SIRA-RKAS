<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->decimal('volume', 15, 2)->nullable()->after('jumlah');
            $table->string('satuan', 50)->nullable()->after('volume');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->dropColumn(['volume', 'satuan']);
        });
    }
};
