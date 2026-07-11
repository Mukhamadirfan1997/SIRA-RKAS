<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rkas_item', function (Blueprint $table) {
            $table->dropColumn(['rencana_tahap1', 'rencana_tahap2']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rkas_item', function (Blueprint $table) {
            $table->decimal('rencana_tahap1', 15, 2)->default(0);
            $table->decimal('rencana_tahap2', 15, 2)->default(0);
        });
    }
};
