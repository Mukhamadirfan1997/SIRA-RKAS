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
        Schema::create('profil_sekolah', function (Blueprint $table) {
            $table->id();
            $table->string('npsn', 15)->unique();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('nama_kepsek')->nullable();
            $table->string('nip_kepsek', 30)->nullable();
            $table->string('nama_bendahara')->nullable();
            $table->string('nip_bendahara', 30)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profil_sekolah');
    }
};
