<?php

namespace Database\Factories;

use App\Models\ProfilSekolah;
use App\Models\RkasItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransaksiBkuFactory extends Factory
{
    public function definition(): array
    {
        $jenis = fake()->randomElement(['penerimaan', 'pengeluaran']);
        return [
            'sekolah_id' => ProfilSekolah::factory(),
            'rkas_item_id' => RkasItem::factory(),
            'tanggal' => fake()->date(),
            'bulan' => fake()->numberBetween(1, 12),
            'no_bukti' => fake()->unique()->numerify('BKU-####-#####'),
            'jenis' => $jenis,
            'jumlah' => fake()->randomFloat(2, 10000, 5000000),
            'toko_penerima' => $jenis === 'pengeluaran' ? fake()->company() : null,
            'uraian' => fake()->sentence(5),
            'status_lunas' => fake()->boolean(80),
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
