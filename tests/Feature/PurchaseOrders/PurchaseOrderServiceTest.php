<?php

use App\Enums\PurchaseOrderStatus;
use App\Exceptions\PurchaseOrderException;
use App\Models\PurchaseOrder;
use App\Models\Shop;
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

test('placing an order needs at least one line', function () {
    $order = draft();
    $order->lines()->delete();

    $this->orders->markOrdered($order);
})->throws(PurchaseOrderException::class, 'at least one line');

test('drafts and untouched orders can be cancelled', function () {
    $cancelled = $this->orders->cancel(draft());
    $placed = $this->orders->cancel($this->orders->markOrdered(draft()));

    expect($cancelled->status)->toBe(PurchaseOrderStatus::Cancelled)
        ->and($placed->status)->toBe(PurchaseOrderStatus::Cancelled);
});
