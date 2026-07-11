<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

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
