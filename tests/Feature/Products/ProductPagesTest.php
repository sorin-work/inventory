<?php

use App\Livewire\Products\Index;
use App\Livewire\Products\Show;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Services\StockService;
use Livewire\Livewire;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->actingAs($this->user = owner($this->shop));
    $this->stock = app(StockService::class);
});

test('lists products with their stock', function () {
    $mug = variant($this->shop, ['sku' => 'MUG-1'], ['name' => 'Stoneware mug']);
    $this->stock->receive($mug, mainLocation($this->shop), 7, 1000);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertSee('Stoneware mug')
        ->assertSee('MUG-1');
});

test('searches by name, sku and barcode', function () {
    variant($this->shop, ['sku' => 'MUG-1', 'barcode' => '96385074'], ['name' => 'Stoneware mug']);
    variant($this->shop, ['sku' => 'PEN-1'], ['name' => 'Brass pen']);

    Livewire::test(Index::class)
        ->set('search', 'stoneware')->assertSee('Stoneware mug')->assertDontSee('Brass pen')
        ->set('search', 'pen-1')->assertSee('Brass pen')->assertDontSee('Stoneware mug')
        ->set('search', '96385074')->assertSee('Stoneware mug')->assertDontSee('Brass pen');
});

test('search treats % and _ as plain characters', function () {
    variant($this->shop, ['sku' => 'SOAP-1'], ['name' => '100% olive oil soap']);
    variant($this->shop, ['sku' => 'PEN-1'], ['name' => 'Brass pen']);

    Livewire::test(Index::class)
        ->set('search', '100%')->assertSee('olive oil soap')->assertDontSee('Brass pen')
        ->set('search', '%')->assertSee('olive oil soap')->assertDontSee('Brass pen');
});

test('filters by category, low stock and archived', function () {
    $candles = Category::factory()->create(['shop_id' => $this->shop->id]);
    variant($this->shop, ['reorder_point' => 5], ['name' => 'Fig candle', 'category_id' => $candles->id]);
    $plenty = variant($this->shop, ['reorder_point' => 1], ['name' => 'Brass pen']);
    $this->stock->receive($plenty, mainLocation($this->shop), 10, 100);
    variant($this->shop, [], ['name' => 'Old stock', 'archived_at' => now()]);

    Livewire::test(Index::class)
        ->assertDontSee('Old stock')
        ->set('categoryId', (string) $candles->id)->assertSee('Fig candle')->assertDontSee('Brass pen')
        ->set('categoryId', '')->set('status', 'low')->assertSee('Fig candle')->assertDontSee('Brass pen')
        ->set('status', 'archived')->assertSee('Old stock')->assertDontSee('Fig candle');
});

test('the product page shows stock per location and the ledger', function () {
    $variant = variant($this->shop, [], ['name' => 'Stoneware mug']);
    $back = otherLocation($this->shop, 'Back room');
    $this->stock->receive($variant, $back, 12, 1150, $this->user, 'First delivery');
    $this->stock->transfer($variant, $back, mainLocation($this->shop), 5, $this->user);

    $this->get(route('products.show', $variant->product))
        ->assertOk()
        ->assertSee('Back room')
        ->assertSee('Transfer out')
        ->assertSee('First delivery')
        ->assertSee('$138.00'); // 12 x $11.50
});

test('the ledger can be filtered by movement type', function () {
    $variant = variant($this->shop);
    $this->stock->receive($variant, mainLocation($this->shop), 5, 100, null, 'Delivery note 1');
    $this->stock->sell($variant, mainLocation($this->shop), 1, null, null, 'Market sale');

    Livewire::test(Show::class, ['product' => $variant->product])
        ->assertSee('Delivery note 1')
        ->set('typeFilter', 'sale')
        ->assertSee('Market sale')
        ->assertDontSee('Delivery note 1');
});

test('owners can archive and restore products', function () {
    $variant = variant($this->shop);

    Livewire::test(Show::class, ['product' => $variant->product])->call('archive');
    expect($variant->product->fresh()->isArchived())->toBeTrue();

    Livewire::test(Show::class, ['product' => $variant->product->fresh()])->call('restore');
    expect($variant->product->fresh()->isArchived())->toBeFalse();
});

test('products without history can be deleted, others can not', function () {
    $unused = variant($this->shop);
    $used = variant($this->shop);
    $this->stock->receive($used, mainLocation($this->shop), 1, 100);

    Livewire::test(Show::class, ['product' => $used->product])->call('delete')->assertNoRedirect();
    Livewire::test(Show::class, ['product' => $unused->product])->call('delete')->assertRedirect(route('products.index'));

    expect(Product::pluck('id')->all())->toBe([$used->product_id]);
});
