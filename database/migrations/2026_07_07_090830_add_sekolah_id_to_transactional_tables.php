<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah sekolah_id ke rkas_item (nullable dulu untuk backward compat)
        Schema::table('rkas_item', function (Blueprint $table) {
            $table->foreignId('sekolah_id')->nullable()->after('id')
                  ->constrained('profil_sekolah')->onDelete('cascade');
        });

        // Tambah sekolah_id ke transaksi_bku
        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->foreignId('sekolah_id')->nullable()->after('id')
                  ->constrained('profil_sekolah')->onDelete('cascade');
        });

        // Tambah sekolah_id ke users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('sekolah_id')->nullable()->after('id')
                  ->constrained('profil_sekolah')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('rkas_item', function (Blueprint $table) {
            $table->dropForeign(['sekolah_id']);
            $table->dropColumn('sekolah_id');
        });
        Schema::table('transaksi_bku', function (Blueprint $table) {
            $table->dropForeign(['sekolah_id']);
            $table->dropColumn('sekolah_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['sekolah_id']);
            $table->dropColumn('sekolah_id');
        });
    }
};
