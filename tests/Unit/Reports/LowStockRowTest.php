<?php

use App\Models\ProductVariant;
use App\Services\Reports\LowStockRow;

function lowStockRow(int $onHand, int $onOrder, int $point, ?int $quantity = null): LowStockRow
{
    $variant = new ProductVariant;
    $variant->reorder_point = $point;
    $variant->reorder_quantity = $quantity;

    return new LowStockRow($variant, $onHand, $onOrder);
}

test('orders the reorder quantity when one is set', function () {
    expect(lowStockRow(onHand: 2, onOrder: 0, point: 5, quantity: 12)->suggestedQuantity())->toBe(12);
});

test('orders enough to get back above the reorder point', function () {
    // 5 - 2 + 1 = 4 brings stock to 6, above the point of 5
    expect(lowStockRow(onHand: 2, onOrder: 0, point: 5)->suggestedQuantity())->toBe(4)
        ->and(lowStockRow(onHand: -3, onOrder: 0, point: 5, quantity: 4)->suggestedQuantity())->toBe(9);
});

test('stock already on order counts towards the suggestion', function () {
    $row = lowStockRow(onHand: 1, onOrder: 10, point: 5, quantity: 12);

    expect($row->available())->toBe(11)
        ->and($row->suggestedQuantity())->toBe(0)
        ->and($row->isCoveredByOpenOrders())->toBeTrue();
});
