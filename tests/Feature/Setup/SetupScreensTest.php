<?php

use App\Livewire\Categories;
use App\Livewire\Locations;
use App\Livewire\Suppliers;
use App\Models\Category;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\Shop;
use App\Models\Supplier;
use App\Services\StockService;
use Livewire\Livewire;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->actingAs($this->user = owner($this->shop));
});

test('the setup pages render', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['suppliers.index', 'suppliers.create', 'categories.index', 'locations.index']);

test('adds and edits a supplier', function () {
    Livewire::test(Suppliers\Form::class)
        ->set('name', 'Fold Paper Goods')
        ->set('email', 'theo@foldpaper.example')
        ->set('lead_time_days', '10')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('suppliers.index'));

    $supplier = Supplier::sole();
    expect($supplier->lead_time_days)->toBe(10);

    Livewire::test(Suppliers\Form::class, ['supplier' => $supplier])
        ->assertSet('name', 'Fold Paper Goods')
        ->set('phone', '+1 503 555 0178')
        ->call('save');

    expect($supplier->fresh()->phone)->toBe('+1 503 555 0178');
});

test('suppliers with purchase orders can not be deleted', function () {
    $used = supplier($this->shop);
    PurchaseOrder::factory()->create(['shop_id' => $this->shop->id, 'supplier_id' => $used->id]);
    $unused = supplier($this->shop);
    $product = variant($this->shop, [], ['supplier_id' => $unused->id])->product;

    Livewire::test(Suppliers\Index::class)
        ->call('delete', $used->id)
        ->call('delete', $unused->id);

    expect(Supplier::pluck('id')->all())->toBe([$used->id])
        ->and($product->fresh()->supplier_id)->toBeNull();
});

test('manages categories', function () {
    $component = Livewire::test(Categories\Index::class)
        ->set('name', 'Candles')
        ->call('create')
        ->assertHasNoErrors();

    expect(Category::pluck('name')->all())->toBe(['Candles']);

    $category = Category::sole();

    $component->call('edit', $category->id)
        ->set('editingName', 'Candles & home fragrance')
        ->call('update')
        ->assertHasNoErrors();

    expect($category->fresh()->name)->toBe('Candles & home fragrance');

    $component->call('delete', $category->id);

    expect(Category::count())->toBe(0);
});

test('adds locations and changes the default', function () {
    $main = mainLocation($this->shop);

    Livewire::test(Locations\Index::class)
        ->set('name', 'Back room')
        ->set('code', 'back')
        ->call('create')
        ->assertHasNoErrors();

    $back = Location::where('name', 'Back room')->sole();
    expect($back->code)->toBe('BACK')->and($back->is_default)->toBeFalse();

    Livewire::test(Locations\Index::class)->call('makeDefault', $back->id);

    expect($back->fresh()->is_default)->toBeTrue()
        ->and($main->fresh()->is_default)->toBeFalse()
        ->and($this->shop->defaultLocation()->is($back))->toBeTrue();
});

test('locations with stock history or the last location can not be deleted', function () {
    $main = mainLocation($this->shop);
    app(StockService::class)->receive(variant($this->shop), $main, 1, 100);
    $empty = otherLocation($this->shop);

    Livewire::test(Locations\Index::class)
        ->call('delete', $main->id)
        ->call('delete', $empty->id);

    expect(Location::pluck('id')->all())->toBe([$main->id]);
});
