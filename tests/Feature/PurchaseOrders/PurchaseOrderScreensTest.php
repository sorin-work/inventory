<?php

use App\Enums\PurchaseOrderStatus;
use App\Livewire\PurchaseOrders\Form;
use App\Livewire\PurchaseOrders\Index;
use App\Livewire\PurchaseOrders\Show;
use App\Models\PurchaseOrder;
use App\Models\Shop;
use App\Services\PurchaseOrderService;
use Livewire\Livewire;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->location = mainLocation($this->shop);
    $this->actingAs($this->user = owner($this->shop));
    $this->supplier = supplier($this->shop, ['name' => 'Riverbend Ceramics', 'lead_time_days' => 21]);
    $this->mug = variant($this->shop, ['sku' => 'MUG', 'average_cost' => 1150], ['name' => 'Stoneware mug', 'supplier_id' => $this->supplier->id]);
});

function placedOrder(): PurchaseOrder
{
    $test = test();
    $orders = app(PurchaseOrderService::class);

    return $orders->markOrdered($orders->createDraft($test->shop, $test->supplier, $test->location, [
        ['product_variant_id' => $test->mug->id, 'quantity_ordered' => 12, 'unit_cost_cents' => 1100],
    ]));
}

test('creates a draft order', function () {
    Livewire::test(Form::class)
        ->set('supplierId', (string) $this->supplier->id)
        ->assertSet('expectedOn', today()->addDays(21)->toDateString())
        ->call('selectVariant', $this->mug->id)
        ->assertSet('lines.0.quantity', '1')
        ->assertSet('lines.0.unit_cost', '11.50')
        ->set('lines.0.unit_cost', '11.00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $order = PurchaseOrder::sole();

    expect($order->status)->toBe(PurchaseOrderStatus::Draft)
        ->and($order->number)->toBe('PO-0001')
        ->and($order->user_id)->toBe($this->user->id)
        ->and($order->lines->sole()->unit_cost_cents)->toBe(1100);
});

test('an order needs a supplier and lines', function () {
    Livewire::test(Form::class)
        ->call('save')
        ->assertHasErrors(['supplierId', 'lines']);
});

test('edits a draft', function () {
    $order = app(PurchaseOrderService::class)->createDraft($this->shop, $this->supplier, $this->location, [
        ['product_variant_id' => $this->mug->id, 'quantity_ordered' => 2, 'unit_cost_cents' => 1100],
    ]);

    Livewire::test(Form::class, ['purchaseOrder' => $order])
        ->assertSet('lines.0.quantity', '2')
        ->set('lines.0.quantity', '5')
        ->call('save')
        ->assertHasNoErrors();

    expect($order->fresh()->lines->sole()->quantity_ordered)->toBe(5);
});

test('placed orders can not be edited', function () {
    $this->get(route('purchase-orders.edit', placedOrder()))->assertForbidden();
});

test('places, receives in two deliveries and completes an order', function () {
    $order = app(PurchaseOrderService::class)->createDraft($this->shop, $this->supplier, $this->location, [
        ['product_variant_id' => $this->mug->id, 'quantity_ordered' => 12, 'unit_cost_cents' => 1100],
    ]);
    $line = $order->lines->first();

    $component = Livewire::test(Show::class, ['purchaseOrder' => $order])
        ->call('placeOrder')
        ->assertSee('Ordered')
        ->call('startReceiving')
        ->set("receive.{$line->id}", '5')
        ->call('saveReceipt')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::PartiallyReceived)
        ->and($this->mug->quantityAt($this->location))->toBe(5);

    $component->call('startReceiving')
        ->set("receive.{$line->id}", '7')
        ->call('saveReceipt')
        ->assertSee('Received');

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received)
        ->and($this->mug->quantityAt($this->location))->toBe(12);
});

test('over receiving shows an error', function () {
    $order = placedOrder();
    $line = $order->lines->first();

    Livewire::test(Show::class, ['purchaseOrder' => $order])
        ->call('startReceiving')
        ->set("receive.{$line->id}", '20')
        ->call('saveReceipt')
        ->assertHasErrors('order');
});

test('owners can cancel an order', function () {
    $order = placedOrder();

    Livewire::test(Show::class, ['purchaseOrder' => $order])->call('cancelOrder');
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Cancelled);
});

test('lists open orders first and filters by status', function () {
    $placed = placedOrder();
    $draft = app(PurchaseOrderService::class)->createDraft($this->shop, $this->supplier, $this->location, []);

    Livewire::test(Index::class)
        ->assertSee($placed->number)
        ->assertDontSee($draft->number)
        ->set('status', 'draft')
        ->assertSee($draft->number)
        ->assertDontSee($placed->number)
        ->set('status', 'all')
        ->assertSee($draft->number)
        ->assertSee($placed->number);
});
