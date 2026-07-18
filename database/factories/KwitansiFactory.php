<?php

namespace Database\Factories;

use App\Models\ProfilSekolah;
use App\Models\TransaksiBku;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kwitansi> */
class KwitansiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transaksi_bku_id' => TransaksiBku::factory(),
            'sekolah_id' => ProfilSekolah::factory(),
            'nomor' => fake()->unique()->numerify('KWT-####-#####'),
            'dicetak_pada' => now(),
            'file_pdf_path' => 'kwitansi/kwitansi-' . fake()->word() . '.pdf',
        ];
    }
}
