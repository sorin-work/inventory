<?php

use App\Models\Category;
use App\Models\Shop;
use App\Models\Supplier;

beforeEach(function () {
    $this->mine = Shop::factory()->create();
    $this->theirs = Shop::factory()->create();
    $this->user = owner($this->mine);
});

test('queries only see the signed in shop', function () {
    Category::factory()->create(['shop_id' => $this->mine->id, 'name' => 'Candles']);
    Category::factory()->create(['shop_id' => $this->theirs->id, 'name' => 'Tea']);
    supplier($this->theirs);

    $this->actingAs($this->user);

    expect(Category::pluck('name')->all())->toBe(['Candles'])
        ->and(Supplier::count())->toBe(0);
});

test('new records are stamped with the signed in shop', function () {
    $this->actingAs($this->user);

    $supplier = Supplier::create(['name' => 'Fold Paper Goods']);

    expect($supplier->shop_id)->toBe($this->mine->id);
});

test('without a signed in user every shop is visible', function () {
    Category::factory()->create(['shop_id' => $this->mine->id]);
    Category::factory()->create(['shop_id' => $this->theirs->id]);

    expect(Category::count())->toBe(2);
});
