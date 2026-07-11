<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SumberDanaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->lexify('SD-????'),
            'nama' => fake()->word(),
        ];
    }
}
