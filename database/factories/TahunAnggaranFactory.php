<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TahunAnggaran> */
class TahunAnggaranFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tahun' => fake()->unique()->year(),
            'status' => false,
        ];
    }
}
