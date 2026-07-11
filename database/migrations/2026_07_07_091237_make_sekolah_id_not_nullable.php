<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rkas_item', function (Blueprint $table) {
            $table->foreignId('sekolah_id')->nullable(false)->change();
        });
        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->foreignId('sekolah_id')->nullable(false)->change();
        });

    }

    public function down(): void
    {
        Schema::table('rkas_item', function (Blueprint $table) {
            $table->foreignId('sekolah_id')->nullable(true)->change();
        });
        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->foreignId('sekolah_id')->nullable(true)->change();
        });

    }
};
