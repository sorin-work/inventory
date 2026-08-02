<?php

use App\Livewire\Reports\LowStock;
use App\Livewire\Reports\Valuation;
use App\Models\PurchaseOrder;
use App\Models\Shop;
use App\Services\StockService;
use Livewire\Livewire;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->location = mainLocation($this->shop);
    $this->actingAs($this->user = owner($this->shop));
    $this->supplier = supplier($this->shop, ['name' => 'Wick & Wax Co.']);
});

test('the low stock report suggests and preselects what to order', function () {
    $candle = variant($this->shop, ['sku' => 'CANDLE', 'reorder_point' => 6, 'reorder_quantity' => 12], ['supplier_id' => $this->supplier->id]);
    $loose = variant($this->shop, ['sku' => 'LOOSE', 'reorder_point' => 2]);

    Livewire::test(LowStock::class)
        ->assertSee('CANDLE')
        ->assertSee('No supplier')
        ->assertSet("order.{$candle->id}", '12')
        ->assertSet("order.{$loose->id}", '3');
});

test('creates draft orders from the selection', function () {
    $candle = variant($this->shop, ['sku' => 'CANDLE', 'reorder_point' => 6, 'reorder_quantity' => 12, 'average_cost' => 950], ['supplier_id' => $this->supplier->id]);

    Livewire::test(LowStock::class)
        ->set("order.{$candle->id}", '20')
        ->call('createOrders')
        ->assertHasNoErrors()
        ->assertRedirect();

    $order = PurchaseOrder::sole();
    expect($order->supplier_id)->toBe($this->supplier->id)
        ->and($order->lines->sole()->quantity_ordered)->toBe(20)
        ->and($order->lines->sole()->unit_cost_cents)->toBe(950);
});

test('needs a selection to create orders', function () {
    variant($this->shop, ['reorder_point' => 6]);

    Livewire::test(LowStock::class)
        ->set('selected', [])
        ->call('createOrders')
        ->assertHasErrors('selected');
});

test('says when nothing is low', function () {
    $this->get(route('reports.low-stock'))->assertOk()->assertSee('Nothing is running low');
});

test('the valuation page shows totals', function () {
    $variant = variant($this->shop, [], ['name' => 'Stoneware mug']);
    app(StockService::class)->receive($variant, $this->location, 4, 1150);

    $this->get(route('reports.valuation'))
        ->assertOk()
        ->assertSee('$46.00')
        ->assertSee('Stoneware mug');

    Livewire::test(Valuation::class)
        ->set('locationId', (string) otherLocation($this->shop)->id)
        ->assertSee('No stock on hand');
});
