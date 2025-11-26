<?php

use App\Models\Location;
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

function supplier(Shop $shop, array $attributes = []): Supplier
{
    return Supplier::factory()->create(['shop_id' => $shop->id, ...$attributes]);
}
