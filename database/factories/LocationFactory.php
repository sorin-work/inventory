<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => fake()->unique()->city().' '.fake()->randomElement(['warehouse', 'stockroom', 'shop', 'stall']),
            'code' => null,
            'is_default' => false,
        ];
    }
}
