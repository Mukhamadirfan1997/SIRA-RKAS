<?php

namespace Database\Factories;

use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kecamatan> */
class KecamatanFactory extends Factory
{
    protected $model = Kecamatan::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->city(),
            'kabupaten' => fake()->city(),
            'provinsi' => fake()->city(),
        ];
    }
}
