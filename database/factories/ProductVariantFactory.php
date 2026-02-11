<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'shop_id' => fn (array $attributes) => Product::withoutGlobalScopes()->findOrFail($attributes['product_id'])->shop_id,
            'sku' => strtoupper(fake()->unique()->bothify('???-####')),
            'price_cents' => fake()->numberBetween(300, 9000),
            'average_cost' => 0,
        ];
    }

    public function reorderAt(int $point, ?int $quantity = null): static
    {
        return $this->state(fn () => ['reorder_point' => $point, 'reorder_quantity' => $quantity]);
    }
}
