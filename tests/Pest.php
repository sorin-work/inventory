<?php

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
| Helpers for building a shop with stock. Factories create a shop with a
| default "Main store" location, like signing up does.
*/

function owner(?Shop $shop = null): User
{
    return User::factory()->create($shop ? ['shop_id' => $shop->id] : []);
}

function staff(Shop $shop): User
{
    return User::factory()->staff()->create(['shop_id' => $shop->id]);
}

function mainLocation(Shop $shop): Location
{
    return $shop->defaultLocation();
}

function otherLocation(Shop $shop, string $name = 'Back room'): Location
{
    return Location::factory()->create(['shop_id' => $shop->id, 'name' => $name]);
}

/**
 * @param  array<string, mixed>  $variant
 * @param  array<string, mixed>  $product
 */
function variant(Shop $shop, array $variant = [], array $product = []): ProductVariant
{
    $model = Product::factory()->create(['shop_id' => $shop->id, ...$product]);

    return ProductVariant::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $model->id,
        ...$variant,
    ])->load('product');
}

function supplier(Shop $shop, array $attributes = []): Supplier
{
    return Supplier::factory()->create(['shop_id' => $shop->id, ...$attributes]);
}

/**
 * Writes CSV content to a temporary file and returns its path.
 */
function csvFile(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, $contents);

    return $path;
}
