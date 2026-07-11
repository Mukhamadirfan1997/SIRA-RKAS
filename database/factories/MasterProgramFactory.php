<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MasterProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->numerify('P.##.##'),
            'nama' => fake()->sentence(3),
            'level' => 1,
        ];
    }
}
