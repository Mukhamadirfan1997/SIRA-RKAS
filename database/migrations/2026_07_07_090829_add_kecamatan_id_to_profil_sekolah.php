<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            // Tambah kecamatan_id FK (nullable dulu, bisa diisi belakangan)
            $table->foreignId('kecamatan_id')->nullable()->after('id')
                  ->constrained('kecamatan')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->dropForeign(['kecamatan_id']);
            $table->dropColumn('kecamatan_id');
        });
    }
};
