<?php

use App\Enums\PurchaseOrderStatus;
use App\Exceptions\PurchaseOrderException;
use App\Models\PurchaseOrder;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Services\PurchaseOrderService;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->location = mainLocation($this->shop);
    $this->supplier = supplier($this->shop);
    $this->mug = variant($this->shop, ['sku' => 'MUG'], ['supplier_id' => $this->supplier->id]);
    $this->bowl = variant($this->shop, ['sku' => 'BOWL'], ['supplier_id' => $this->supplier->id]);
    $this->orders = app(PurchaseOrderService::class);
});

function draft(array $lines = []): PurchaseOrder
{
    $test = test();

    return $test->orders->createDraft($test->shop, $test->supplier, $test->location, $lines ?: [
        ['product_variant_id' => $test->mug->id, 'quantity_ordered' => 10, 'unit_cost_cents' => 1150],
        ['product_variant_id' => $test->bowl->id, 'quantity_ordered' => 4, 'unit_cost_cents' => 1500],
    ]);
}

test('drafts are numbered per shop', function () {
    $first = draft();
    $second = draft();
    $elsewhere = $this->orders->nextNumber(Shop::factory()->create());

    expect($first->number)->toBe('PO-0001')
        ->and($second->number)->toBe('PO-0002')
        ->and($elsewhere)->toBe('PO-0001')
        ->and($first->status)->toBe(PurchaseOrderStatus::Draft)
        ->and($first->totalCents())->toBe(10 * 1150 + 4 * 1500);
});

test('numbers are not reused after a draft is deleted', function () {
    draft();
    $second = draft();
    draft()->delete();
    $second->delete();

    expect(draft()->number)->toBe('PO-0002');
});

test('placing an order needs at least one line', function () {
    $order = draft();
    $order->lines()->delete();

    $this->orders->markOrdered($order);
})->throws(PurchaseOrderException::class, 'at least one line');

test('receiving part of an order books stock and marks it partially received', function () {
    $order = $this->orders->markOrdered(draft());
    $mugLine = $order->lines->firstWhere('product_variant_id', $this->mug->id);

    $order = $this->orders->receive($order, [$mugLine->id => 6]);

    expect($order->status)->toBe(PurchaseOrderStatus::PartiallyReceived)
        ->and($order->received_at)->toBeNull()
        ->and($mugLine->fresh()->quantity_received)->toBe(6)
        ->and($this->mug->quantityAt($this->location))->toBe(6)
        ->and($this->mug->fresh()->average_cost)->toBe(1150.0);

    $movement = StockMovement::sole();
    expect($movement->purchase_order_id)->toBe($order->id)
        ->and($movement->note)->toBe($order->number);
});

test('receiving the rest completes the order', function () {
    $order = $this->orders->markOrdered(draft());
    [$mugLine, $bowlLine] = $order->lines->all();

    $this->orders->receive($order, [$mugLine->id => 6]);
    $order = $this->orders->receive($order->fresh(), [$mugLine->id => 4, $bowlLine->id => 4]);

    expect($order->status)->toBe(PurchaseOrderStatus::Received)
        ->and($order->received_at)->not->toBeNull()
        ->and($this->mug->fresh()->onHand())->toBe(10)
        ->and($this->bowl->fresh()->onHand())->toBe(4);
});

test('cannot receive more than is outstanding', function () {
    $order = $this->orders->markOrdered(draft());
    $line = $order->lines->first();

    expect(fn () => $this->orders->receive($order, [$line->id => 11]))
        ->toThrow(PurchaseOrderException::class, 'Only 10');

    expect(StockMovement::count())->toBe(0);
});

test('receiving needs at least one quantity', function () {
    $order = $this->orders->markOrdered(draft());

    $this->orders->receive($order, [$order->lines->first()->id => 0]);
})->throws(PurchaseOrderException::class, 'at least one line');

test('drafts and finished orders cannot be received', function () {
    $this->orders->receive(draft(), [1 => 1]);
})->throws(PurchaseOrderException::class, 'draft order cannot be received');

test('drafts and untouched orders can be cancelled', function () {
    $cancelled = $this->orders->cancel(draft());
    $placed = $this->orders->cancel($this->orders->markOrdered(draft()));

    expect($cancelled->status)->toBe(PurchaseOrderStatus::Cancelled)
        ->and($placed->status)->toBe(PurchaseOrderStatus::Cancelled);
});

test('orders with deliveries cannot be cancelled', function () {
    $order = $this->orders->markOrdered(draft());
    $order = $this->orders->receive($order, [$order->lines->first()->id => 1]);

    $this->orders->cancel($order->fresh('lines'));
})->throws(PurchaseOrderException::class);

test('low stock selections become one draft per supplier', function () {
    $other = supplier($this->shop, ['name' => 'Leaf Lane Tea']);
    $tea = variant($this->shop, ['sku' => 'TEA', 'average_cost' => 480.4], ['supplier_id' => $other->id]);
    $loose = variant($this->shop, ['sku' => 'LOOSE']);

    $result = $this->orders->draftsFromLowStock($this->shop, $this->location, [
        $this->mug->id => 12,
        $this->bowl->id => 6,
        $tea->id => 24,
        $loose->id => 5,
    ]);

    expect($result['orders'])->toHaveCount(2)
        ->and($result['skipped']->pluck('sku')->all())->toBe(['LOOSE']);

    $teaOrder = $result['orders']->firstWhere('supplier_id', $other->id);
    expect($teaOrder->lines)->toHaveCount(1)
        ->and($teaOrder->lines->first()->quantity_ordered)->toBe(24)
        ->and($teaOrder->lines->first()->unit_cost_cents)->toBe(480);
});
