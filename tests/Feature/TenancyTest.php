<?php

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Shop;
use App\Models\Supplier;

beforeEach(function () {
    $this->mine = Shop::factory()->create();
    $this->theirs = Shop::factory()->create();
    $this->user = owner($this->mine);
});

test('queries only see the signed in shop', function () {
    variant($this->mine, [], ['name' => 'My mug']);
    variant($this->theirs, [], ['name' => 'Their mug']);
    supplier($this->theirs);

    $this->actingAs($this->user);

    expect(Product::pluck('name')->all())->toBe(['My mug'])
        ->and(Supplier::count())->toBe(0);
});

test('records from another shop are not found', function () {
    $theirs = variant($this->theirs);
    $order = PurchaseOrder::factory()->create(['shop_id' => $this->theirs->id]);
    $supplier = supplier($this->theirs);

    $this->actingAs($this->user);

    $this->get(route('products.show', $theirs->product_id))->assertNotFound();
    $this->get(route('products.edit', $theirs->product_id))->assertNotFound();
    $this->get(route('purchase-orders.show', $order))->assertNotFound();
    $this->get(route('suppliers.edit', $supplier))->assertNotFound();
});

test('new records are stamped with the signed in shop', function () {
    $this->actingAs($this->user);

    $supplier = Supplier::create(['name' => 'Fold Paper Goods']);

    expect($supplier->shop_id)->toBe($this->mine->id);
});

test('product exports only contain the shop\'s own products', function () {
    variant($this->mine, ['sku' => 'MINE-1']);
    variant($this->theirs, ['sku' => 'THEIRS-1']);

    $csv = $this->actingAs($this->user)->get(route('products.export'))->streamedContent();

    expect($csv)->toContain('MINE-1')->not->toContain('THEIRS-1');
});
