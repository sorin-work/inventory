<?php

use App\Livewire\Products\Index;
use App\Models\Category;
use App\Models\Shop;
use Livewire\Livewire;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->actingAs($this->user = owner($this->shop));
});

test('lists products', function () {
    variant($this->shop, ['sku' => 'MUG-1'], ['name' => 'Stoneware mug']);

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

test('filters by category and archived', function () {
    $candles = Category::factory()->create(['shop_id' => $this->shop->id]);
    variant($this->shop, [], ['name' => 'Fig candle', 'category_id' => $candles->id]);
    variant($this->shop, [], ['name' => 'Brass pen']);
    variant($this->shop, [], ['name' => 'Old stock', 'archived_at' => now()]);

    Livewire::test(Index::class)
        ->assertDontSee('Old stock')
        ->set('categoryId', (string) $candles->id)->assertSee('Fig candle')->assertDontSee('Brass pen')
        ->set('categoryId', '')->set('status', 'archived')->assertSee('Old stock')->assertDontSee('Fig candle');
});
