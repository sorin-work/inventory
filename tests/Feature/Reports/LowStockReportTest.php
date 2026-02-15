<?php

use App\Models\Shop;
use App\Services\PurchaseOrderService;
use App\Services\Reports\LowStockReport;
use App\Services\StockService;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->location = mainLocation($this->shop);
    $this->stock = app(StockService::class);
    $this->report = app(LowStockReport::class);
});

function stocked(int $quantity, ?int $point, array $product = [], ?int $reorderQuantity = null)
{
    $test = test();
    $variant = variant($test->shop, ['reorder_point' => $point, 'reorder_quantity' => $reorderQuantity], $product);

    if ($quantity > 0) {
        $test->stock->receive($variant, $test->location, $quantity, 100);
    }

    return $variant;
}

test('lists variants at or below their reorder point', function () {
    $at = stocked(5, 5);
    $below = stocked(1, 5);
    $out = stocked(0, 3);
    stocked(6, 5);
    stocked(0, null);

    $ids = $this->report->rows($this->shop)->map(fn ($row) => $row->variant->id)->all();

    expect($ids)->toEqualCanonicalizing([$at->id, $below->id, $out->id]);
});

test('leaves out archived products and other shops', function () {
    stocked(0, 5, ['archived_at' => now()]);
    variant(Shop::factory()->create(), ['reorder_point' => 5]);

    expect($this->report->rows($this->shop))->toBeEmpty();
});

test('takes open purchase orders into account', function () {
    $supplier = supplier($this->shop);
    $variant = stocked(1, 5, ['supplier_id' => $supplier->id], 12);
    $orders = app(PurchaseOrderService::class);

    $draft = $orders->createDraft($this->shop, $supplier, $this->location, [
        ['product_variant_id' => $variant->id, 'quantity_ordered' => 12, 'unit_cost_cents' => 100],
    ]);

    // Drafts haven't been sent, so they don't count yet.
    expect($this->report->rows($this->shop)->first()->onOrder)->toBe(0);

    $orders->markOrdered($draft);
    $row = $this->report->rows($this->shop)->first();

    expect($row->onOrder)->toBe(12)
        ->and($row->suggestedQuantity())->toBe(0)
        ->and($row->isCoveredByOpenOrders())->toBeTrue();
});
