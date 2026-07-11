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
        Schema::create('master_program', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 100);
            $table->string('nama');
            $table->foreignId('parent_id')->nullable()->constrained('master_program')->onDelete('cascade');
            $table->integer('level')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_program');
    }
};
