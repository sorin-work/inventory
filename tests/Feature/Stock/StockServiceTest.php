<?php

use App\Enums\AdjustmentReason;
use App\Enums\MovementType;
use App\Exceptions\StockException;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Services\StockService;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->main = mainLocation($this->shop);
    $this->back = otherLocation($this->shop);
    $this->variant = variant($this->shop);
    $this->stock = app(StockService::class);
});

test('receiving adds stock, records the movement and sets the cost', function () {
    $user = owner($this->shop);

    $movement = $this->stock->receive($this->variant, $this->main, 10, 450, $user, 'Invoice 1001');

    expect($movement->type)->toBe(MovementType::Receipt)
        ->and($movement->quantity)->toBe(10)
        ->and($movement->balance_after)->toBe(10)
        ->and($movement->unit_cost)->toBe(450.0)
        ->and($movement->user_id)->toBe($user->id)
        ->and($movement->note)->toBe('Invoice 1001')
        ->and($this->variant->quantityAt($this->main))->toBe(10)
        ->and($this->variant->fresh()->average_cost)->toBe(450.0);
});

test('the average starts over once stock has run out', function () {
    $this->stock->receive($this->variant, $this->main, 5, 500);
    $this->stock->sell($this->variant, $this->main, 5);
    $this->stock->receive($this->variant, $this->main, 5, 800);

    expect($this->variant->fresh()->average_cost)->toBe(800.0);
});

test('selling takes stock out at average cost and keeps the price', function () {
    $this->variant->update(['price_cents' => 2400]);
    $this->stock->receive($this->variant, $this->main, 4, 950);

    $sale = $this->stock->sell($this->variant, $this->main, 3);
    $discounted = $this->stock->sell($this->variant, $this->main, 1, 2000);

    expect($sale->quantity)->toBe(-3)
        ->and($sale->balance_after)->toBe(1)
        ->and($sale->unit_cost)->toBe(950.0)
        ->and($sale->unit_price_cents)->toBe(2400)
        ->and($discounted->unit_price_cents)->toBe(2000)
        ->and($this->variant->quantityAt($this->main))->toBe(0)
        ->and($this->variant->fresh()->average_cost)->toBe(950.0);
});

test('cannot sell more than is on hand at the location', function () {
    $this->stock->receive($this->variant, $this->back, 5, 100);

    expect(fn () => $this->stock->sell($this->variant, $this->main, 1))
        ->toThrow(StockException::class, 'Only 0 of');

    expect(StockMovement::count())->toBe(1);
});

test('adjustments add or remove stock with a reason', function () {
    $this->stock->receive($this->variant, $this->main, 10, 300);

    $damaged = $this->stock->adjust($this->variant, $this->main, -2, AdjustmentReason::Damaged, null, 'Chipped');
    $returned = $this->stock->adjust($this->variant, $this->main, 1, AdjustmentReason::Returned);

    expect($damaged->quantity)->toBe(-2)
        ->and($damaged->reason)->toBe(AdjustmentReason::Damaged)
        ->and($damaged->balance_after)->toBe(8)
        ->and($returned->balance_after)->toBe(9)
        ->and($this->variant->fresh()->average_cost)->toBe(300.0);
});

test('adjustments cannot take stock below zero or change nothing', function () {
    $this->stock->receive($this->variant, $this->main, 2, 300);

    expect(fn () => $this->stock->adjust($this->variant, $this->main, -3, AdjustmentReason::Lost))
        ->toThrow(StockException::class)
        ->and(fn () => $this->stock->adjust($this->variant, $this->main, 0, AdjustmentReason::Lost))
        ->toThrow(StockException::class, 'nothing to adjust');
});

test('transfers move stock between locations as a pair of movements', function () {
    $this->stock->receive($this->variant, $this->back, 10, 300);

    [$out, $in] = $this->stock->transfer($this->variant, $this->back, $this->main, 4);

    expect($out->type)->toBe(MovementType::TransferOut)
        ->and($out->quantity)->toBe(-4)
        ->and($out->balance_after)->toBe(6)
        ->and($in->type)->toBe(MovementType::TransferIn)
        ->and($in->quantity)->toBe(4)
        ->and($in->balance_after)->toBe(4)
        ->and($out->transfer_id)->toBe($in->transfer_id)
        ->and($out->counterpart()->is($in))->toBeTrue()
        ->and($this->variant->fresh()->onHand())->toBe(10);
});

test('transfers need two different locations and enough stock', function () {
    $this->stock->receive($this->variant, $this->main, 3, 300);

    expect(fn () => $this->stock->transfer($this->variant, $this->main, $this->main, 1))
        ->toThrow(StockException::class, 'different locations')
        ->and(fn () => $this->stock->transfer($this->variant, $this->main, $this->back, 4))
        ->toThrow(StockException::class, 'Only 3');
});
