<?php

namespace Database\Factories;

use App\Models\RkasItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class RkasItemBulanFactory extends Factory
{
    public function definition(): array
    {
        $rencana = fake()->randomFloat(2, 100000, 10000000);
        return [
            'rkas_item_id' => RkasItem::factory(),
            'bulan' => fake()->numberBetween(1, 12),
            'rencana' => $rencana,
        ];
    }
}
