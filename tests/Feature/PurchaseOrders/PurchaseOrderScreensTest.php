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
    $this->mug = variant($this->shop, ['sku' => 'MUG', 'average_cost' => 1150, 'reorder_quantity' => 12], ['name' => 'Stoneware mug', 'supplier_id' => $this->supplier->id]);
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
        ->assertSet('lines.0.quantity', '12')
        ->assertSet('lines.0.unit_cost', '11.50')
        ->set('lines.0.unit_cost', '11.00')
        ->set('notes', 'Leave at the back door')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $order = PurchaseOrder::sole();

    expect($order->status)->toBe(PurchaseOrderStatus::Draft)
        ->and($order->number)->toBe('PO-0001')
        ->and($order->user_id)->toBe($this->user->id)
        ->and($order->notes)->toBe('Leave at the back door')
        ->and($order->lines->sole()->unit_cost_cents)->toBe(1100);
});

test('picking the same product twice adds to the line', function () {
    Livewire::test(Form::class)
        ->call('selectVariant', $this->mug->id)
        ->call('selectVariant', $this->mug->id)
        ->assertCount('lines', 1)
        ->assertSet('lines.0.quantity', '13');
});

test('an order needs a supplier and lines', function () {
    Livewire::test(Form::class)
        ->call('save')
        ->assertHasErrors(['supplierId', 'lines']);
});

test('the supplier can be passed in the url', function () {
    $this->get(route('purchase-orders.create', ['supplier' => $this->supplier->id]))
        ->assertOk();

    Livewire::withQueryParams(['supplier' => $this->supplier->id])
        ->test(Form::class)
        ->assertSet('supplierId', (string) $this->supplier->id);
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
        ->assertSet("receive.{$line->id}", '12')
        ->set("receive.{$line->id}", '5')
        ->call('saveReceipt')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::PartiallyReceived)
        ->and($this->mug->quantityAt($this->location))->toBe(5);

    $component->call('startReceiving')
        ->assertSet("receive.{$line->id}", '7')
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

test('owners can cancel an order and delete a draft', function () {
    $order = placedOrder();

    Livewire::test(Show::class, ['purchaseOrder' => $order])->call('cancelOrder');
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Cancelled);

    $draft = app(PurchaseOrderService::class)->createDraft($this->shop, $this->supplier, $this->location, []);
    Livewire::test(Show::class, ['purchaseOrder' => $draft])->call('delete')->assertRedirect(route('purchase-orders.index'));
    expect(PurchaseOrder::find($draft->id))->toBeNull();
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

test('flags late orders', function () {
    $order = placedOrder();
    $order->update(['expected_on' => today()->subDay()]);

    $this->get(route('purchase-orders.index'))->assertSee('(late)', false);
});

test('a short delivery can be closed from the order page', function () {
    $order = placedOrder();
    $line = $order->lines->first();

    Livewire::test(Show::class, ['purchaseOrder' => $order])
        ->call('startReceiving')
        ->set("receive.{$line->id}", '8')
        ->call('saveReceipt')
        ->assertSee('Close short')
        ->call('closeShort')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received);
});

test('order again copies the order into a new draft', function () {
    $order = placedOrder();

    Livewire::test(Show::class, ['purchaseOrder' => $order])
        ->call('duplicate')
        ->assertRedirect();

    expect(PurchaseOrder::where('status', 'draft')->sole()->lines)->toHaveCount(1);
});
