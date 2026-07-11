<?php

namespace Database\Factories;

use App\Models\ProfilSekolah;
use App\Models\TahunAnggaran;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sekolah_id' => ProfilSekolah::factory(),
            'tahun_anggaran_id' => TahunAnggaran::factory(),
            'bulan' => fake()->numberBetween(1, 12),
            'file_name' => fake()->word() . '.xlsx',
            'file_path' => 'import_rkas/' . fake()->word() . '.xlsx',
            'status' => fake()->randomElement(['pending', 'processing', 'success', 'failed']),
            'total_baris' => fake()->numberBetween(0, 100),
            'baris_berhasil' => fake()->numberBetween(0, 100),
            'baris_gagal' => fake()->numberBetween(0, 10),
            'uploaded_by' => \App\Models\User::factory(),
        ];
    }
}
