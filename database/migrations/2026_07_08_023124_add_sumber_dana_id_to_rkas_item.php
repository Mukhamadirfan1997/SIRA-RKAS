<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rkas_item', function (Blueprint $table) {
            $table->foreignId('sumber_dana_id')->nullable()->after('kode_rekening_id')->constrained('sumber_dana')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('rkas_item', function (Blueprint $table) {
            $table->dropForeign(['sumber_dana_id']);
            $table->dropColumn('sumber_dana_id');
        });
    }
};
