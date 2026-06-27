<?php

use App\Support\AverageCost;

test('the first receipt sets the cost', function () {
    expect(AverageCost::afterReceipt(0, 0, 10, 500))->toBe(500.0);
});

test('receipts blend with the cost of stock on hand', function () {
    // 10 at $5.00 plus 30 at $7.00 = $260 for 40 units = $6.50
    expect(AverageCost::afterReceipt(10, 500, 30, 700))->toBe(650.0);
});

test('keeps four decimals so repeated receipts do not drift', function () {
    // 3 at $1.00 + 1 at $2.00 = 125 cents; then 3 more at 100
    $avg = AverageCost::afterReceipt(3, 100, 1, 200);
    expect($avg)->toBe(125.0);

    $avg = AverageCost::afterReceipt(4, $avg, 3, 100);
    expect($avg)->toBe(114.2857);
});

test('negative stock on hand resets the cost to the new units', function () {
    expect(AverageCost::afterReceipt(-2, 900, 5, 400))->toBe(400.0);
});

test('rejects receipts that make no sense', function (int $qty, float $cost) {
    AverageCost::afterReceipt(5, 100, $qty, $cost);
})->with([[0, 100], [-1, 100], [3, -1]])->throws(InvalidArgumentException::class);

test('values stock in whole cents', function () {
    expect(AverageCost::value(3, 114.2857))->toBe(343)
        ->and(AverageCost::value(0, 999))->toBe(0);
});
