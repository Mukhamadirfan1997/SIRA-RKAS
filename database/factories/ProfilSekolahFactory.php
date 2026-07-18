<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfilSekolah> */
class ProfilSekolahFactory extends Factory
{
    public function definition(): array
    {
        return [
            'npsn' => fake()->unique()->numerify('##########'),
            'nama' => fake()->company(),
            'alamat' => fake()->address(),
            'kabupaten' => fake()->city(),
            'provinsi' => fake()->city(),
            'nama_kepsek' => fake()->name(),
            'nip_kepsek' => fake()->numerify('####################'),
            'nama_bendahara' => fake()->name(),
            'nip_bendahara' => fake()->numerify('####################'),
        ];
    }
}
