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
        Schema::create('master_kode_rekening', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 100)->unique();
            $table->string('nama');
            $table->foreignId('jenis_belanja_id')->nullable()->constrained('jenis_belanja')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_kode_rekening');
    }
};
