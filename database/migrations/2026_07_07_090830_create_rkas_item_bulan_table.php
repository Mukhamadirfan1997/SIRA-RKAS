<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rkas_item_bulan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkas_item_id')->constrained('rkas_item')->onDelete('cascade');
            $table->tinyInteger('bulan')->unsigned(); // 1–12
            $table->decimal('rencana', 15, 2)->default(0);
            $table->timestamps();

            // Satu item hanya boleh punya 1 record per bulan
            $table->unique(['rkas_item_id', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rkas_item_bulan');
    }
};
