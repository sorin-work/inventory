<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'currency' => 'USD',
        ];
    }

    /**
     * Every real shop has a location from the moment it signs up.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Shop $shop) {
            Location::factory()->for($shop)->create(['name' => 'Main store', 'code' => 'MAIN', 'is_default' => true]);
        });
    }
}
