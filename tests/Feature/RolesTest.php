<?php

use App\Livewire\Categories;
use App\Livewire\Locations;
use App\Livewire\Products\Show;
use App\Livewire\Suppliers;
use App\Models\Category;
use App\Models\Shop;
use App\Services\StockService;
use Livewire\Livewire;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->staff = staff($this->shop);
});

test('staff can not open owner pages', function (string $route) {
    $this->actingAs($this->staff)->get(route($route))->assertForbidden();
})->with(['reports.valuation', 'team.index', 'settings.shop']);

test('owners can open owner pages', function (string $route) {
    $this->actingAs(owner($this->shop))->get(route($route))->assertOk();
})->with(['reports.valuation', 'team.index', 'settings.shop']);

test('staff do not see owner links in the navigation', function () {
    $this->actingAs($this->staff)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Valuation')
        ->assertDontSee('Shop settings');
});

test('staff can not archive or delete products', function () {
    $variant = variant($this->shop);

    Livewire::actingAs($this->staff)
        ->test(Show::class, ['product' => $variant->product])
        ->call('archive')
        ->assertForbidden();

    Livewire::actingAs($this->staff)
        ->test(Show::class, ['product' => $variant->product])
        ->call('delete')
        ->assertForbidden();

    expect($variant->product->fresh()->archived_at)->toBeNull();
});

test('staff can not delete setup records', function () {
    $this->actingAs($this->staff);
    $category = Category::factory()->create(['shop_id' => $this->shop->id]);
    $supplier = supplier($this->shop);
    $location = otherLocation($this->shop);

    Livewire::test(Categories\Index::class)->call('delete', $category->id)->assertForbidden();
    Livewire::test(Suppliers\Index::class)->call('delete', $supplier->id)->assertForbidden();
    Livewire::test(Locations\Index::class)->call('delete', $location->id)->assertForbidden();
});

test('staff do not see stock value on the dashboard', function () {
    $this->actingAs($this->staff)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Stock value')
        ->assertSee('Open orders');
});

test('staff do not see what stock is worth on the product and locations pages', function () {
    $variant = variant($this->shop, [], ['name' => 'Stoneware mug']);
    app(StockService::class)->receive($variant, mainLocation($this->shop), 4, 1150);

    $this->actingAs($this->staff);

    $this->get(route('products.show', $variant->product))->assertOk()->assertDontSee('$46.00');
    $this->get(route('locations.index'))->assertOk()->assertDontSee('$46.00');

    $this->actingAs(owner($this->shop));

    $this->get(route('products.show', $variant->product))->assertSee('$46.00');
    $this->get(route('locations.index'))->assertSee('$46.00');
});
