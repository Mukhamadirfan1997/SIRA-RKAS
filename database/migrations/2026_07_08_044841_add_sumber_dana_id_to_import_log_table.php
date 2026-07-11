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
        Schema::table('import_log', function (Blueprint $table) {
            $table->foreignId('sumber_dana_id')
                ->nullable()
                ->after('bulan')
                ->constrained('sumber_dana')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_log', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sumber_dana_id');
        });
    }
};
