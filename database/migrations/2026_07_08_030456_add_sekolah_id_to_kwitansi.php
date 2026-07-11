<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kwitansi', function (Blueprint $table) {
            $table->foreignId('sekolah_id')->nullable()->after('nomor')->constrained('profil_sekolah')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('kwitansi', function (Blueprint $table) {
            $table->dropForeign(['sekolah_id']);
            $table->dropColumn('sekolah_id');
        });
    }
};
