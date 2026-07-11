<?php

namespace Database\Factories;

use App\Models\JenisBelanja;
use Illuminate\Database\Eloquent\Factories\Factory;

class MasterKodeRekeningFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->numerify('5.#.##.##.##.####'),
            'nama' => fake()->sentence(3),
            'jenis_belanja_id' => JenisBelanja::factory(),
        ];
    }
}
