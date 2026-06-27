<?php

use App\Enums\AdjustmentReason;
use App\Enums\MovementType;
use App\Livewire\Stock\Adjust;
use App\Livewire\Stock\Receive;
use App\Livewire\Stock\Sell;
use App\Livewire\Stock\Transfer;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Services\StockService;
use Livewire\Livewire;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->main = mainLocation($this->shop);
    $this->actingAs($this->user = staff($this->shop));
    $this->variant = variant($this->shop, ['sku' => 'MUG-WHITE', 'barcode' => '4006381333931', 'price_cents' => 2800], ['name' => 'Stoneware mug']);
});

test('the stock pages render', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['stock.receive', 'stock.sell', 'stock.adjust', 'stock.transfer']);

test('scanning a barcode picks the variant', function () {
    Livewire::test(Receive::class)
        ->set('search', '4006381333931')
        ->assertSet('variantId', $this->variant->id)
        ->assertSet('search', '');
});

test('archived products can not be picked', function () {
    $this->variant->product->update(['archived_at' => now()]);

    Livewire::test(Receive::class)
        ->set('search', 'MUG-WHITE')
        ->assertSet('variantId', null);
});

test('receives stock', function () {
    Livewire::test(Receive::class)
        ->call('selectVariant', $this->variant->id)
        ->set('quantity', '12')
        ->set('unitCost', '11.50')
        ->set('note', 'Invoice 2231')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify')
        ->assertSet('variantId', null);

    $movement = StockMovement::sole();

    expect($movement->type)->toBe(MovementType::Receipt)
        ->and($movement->quantity)->toBe(12)
        ->and($movement->unit_cost)->toBe(1150.0)
        ->and($movement->user_id)->toBe($this->user->id)
        ->and($movement->location_id)->toBe($this->main->id);
});

test('receiving validates its fields', function () {
    Livewire::test(Receive::class)
        ->set('quantity', '0')
        ->set('unitCost', 'abc')
        ->call('save')
        ->assertHasErrors(['variantId', 'quantity', 'unitCost']);
});

test('records a sale at the list price', function () {
    app(StockService::class)->receive($this->variant, $this->main, 5, 1150);

    Livewire::test(Sell::class)
        ->call('selectVariant', $this->variant->id)
        ->assertSet('unitPrice', '28.00')
        ->set('quantity', '2')
        ->call('save')
        ->assertHasNoErrors();

    $sale = StockMovement::where('type', 'sale')->sole();
    expect($sale->quantity)->toBe(-2)->and($sale->unit_price_cents)->toBe(2800);
});

test('a sale larger than the stock shows an error', function () {
    Livewire::test(Sell::class)
        ->call('selectVariant', $this->variant->id)
        ->set('quantity', '1')
        ->call('save')
        ->assertHasErrors('quantity');

    expect(StockMovement::count())->toBe(0);
});

test('adjusts stock with a reason', function () {
    app(StockService::class)->receive($this->variant, $this->main, 5, 1150);

    Livewire::test(Adjust::class)
        ->call('selectVariant', $this->variant->id)
        ->set('change', '-2')
        ->set('reason', 'damaged')
        ->set('note', 'Dropped')
        ->call('save')
        ->assertHasNoErrors();

    expect(StockMovement::where('type', 'adjustment')->sole()->reason)->toBe(AdjustmentReason::Damaged)
        ->and($this->variant->quantityAt($this->main))->toBe(3);
});

test('the "other" reason needs a note', function () {
    app(StockService::class)->receive($this->variant, $this->main, 5, 1150);

    Livewire::test(Adjust::class)
        ->call('selectVariant', $this->variant->id)
        ->set('change', '-1')
        ->set('reason', 'other')
        ->call('save')
        ->assertHasErrors('note');
});

test('a zero change is refused', function () {
    Livewire::test(Adjust::class)
        ->call('selectVariant', $this->variant->id)
        ->set('change', '0')
        ->call('save')
        ->assertHasErrors('change');
});

test('records a stock count', function () {
    app(StockService::class)->receive($this->variant, $this->main, 5, 1150);

    Livewire::test(Adjust::class)
        ->set('mode', 'count')
        ->call('selectVariant', $this->variant->id)
        ->set('counted', '7')
        ->call('save')
        ->assertHasNoErrors();

    expect(StockMovement::where('type', 'adjustment')->sole()->reason)->toBe(AdjustmentReason::StockCount)
        ->and($this->variant->quantityAt($this->main))->toBe(7);
});

test('transfers stock', function () {
    $back = otherLocation($this->shop, 'Back room');
    app(StockService::class)->receive($this->variant, $back, 8, 1150);

    Livewire::test(Transfer::class)
        ->assertSet('locationId', $this->main->id)
        ->assertSet('toLocationId', $back->id)
        ->set('locationId', $back->id)
        ->set('toLocationId', $this->main->id)
        ->call('selectVariant', $this->variant->id)
        ->set('quantity', '3')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->variant->quantityAt($back))->toBe(5)
        ->and($this->variant->quantityAt($this->main))->toBe(3);
});

test('a transfer needs two different locations', function () {
    otherLocation($this->shop);

    Livewire::test(Transfer::class)
        ->call('selectVariant', $this->variant->id)
        ->set('toLocationId', $this->main->id)
        ->call('save')
        ->assertHasErrors('toLocationId');
});

test('the transfer page explains that a second location is needed', function () {
    $this->get(route('stock.transfer'))->assertSee('You need two locations');
});

test('shows your recent entries', function () {
    app(StockService::class)->receive($this->variant, $this->main, 4, 100, $this->user);

    Livewire::test(Receive::class)->assertSee('Your recent entries')->assertSee('+4');
});
