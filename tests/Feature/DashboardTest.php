<?php

use App\Livewire\Movements\Index;
use App\Models\Shop;
use App\Services\StockService;
use Livewire\Livewire;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->actingAs($this->user = owner($this->shop));
    $this->stock = app(StockService::class);
});

test('the dashboard summarises stock', function () {
    $variant = variant($this->shop, ['reorder_point' => 5], ['name' => 'Stoneware mug']);
    $this->stock->receive($variant, mainLocation($this->shop), 4, 1000);
    $this->stock->sell($variant, mainLocation($this->shop), 1);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('1 sold in the last 30 days')
        ->assertSee('Stoneware mug');
});

test('the movement ledger filters by type, location and date', function () {
    $variant = variant($this->shop, ['sku' => 'MUG-1']);
    $main = mainLocation($this->shop);
    $back = otherLocation($this->shop);

    $this->travelTo(now()->subDays(10));
    $this->stock->receive($variant, $back, 10, 100, null, 'Old delivery');
    $this->travelBack();
    $this->stock->transfer($variant, $back, $main, 2, null, 'Fresh transfer');

    Livewire::test(Index::class)
        ->assertSee('Old delivery')
        ->set('type', 'receipt')->assertSee('Old delivery')->assertDontSee('Fresh transfer')
        ->set('type', '')->set('location', (string) $main->id)->assertSee('Fresh transfer')->assertDontSee('Old delivery')
        ->set('location', '')->set('from', today()->subDay()->toDateString())->assertDontSee('Old delivery');
});

test('filtering the ledger up to a date includes that whole day', function () {
    $variant = variant($this->shop);
    $this->travelTo(today()->setTime(18, 30));
    $this->stock->receive($variant, mainLocation($this->shop), 1, 100, null, 'Evening delivery');

    Livewire::test(Index::class)
        ->set('to', today()->toDateString())
        ->assertSee('Evening delivery');
});
