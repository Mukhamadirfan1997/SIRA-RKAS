<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JenisBelanja> */
class JenisBelanjaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->word(),
        ];
    }
}
