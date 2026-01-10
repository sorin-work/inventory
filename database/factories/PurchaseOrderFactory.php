<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Shop;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'supplier_id' => fn (array $attributes) => Supplier::factory()->create(['shop_id' => $attributes['shop_id']])->id,
            'location_id' => fn (array $attributes) => Shop::findOrFail($attributes['shop_id'])->defaultLocation()->id,
            'number' => 'PO-'.fake()->unique()->numerify('####'),
            'status' => PurchaseOrderStatus::Draft,
        ];
    }
}
