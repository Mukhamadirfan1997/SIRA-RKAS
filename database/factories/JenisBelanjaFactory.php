<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class JenisBelanjaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->word(),
        ];
    }
}
