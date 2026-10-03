<?php

use App\Models\Category;
use App\Models\Shop;
use App\Services\Reports\ValuationReport;
use App\Services\StockService;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->main = mainLocation($this->shop);
    $this->back = otherLocation($this->shop);
    $this->stock = app(StockService::class);
});

test('values stock at average cost by location and category', function () {
    $candles = Category::factory()->create(['shop_id' => $this->shop->id, 'name' => 'Candles']);
    $candle = variant($this->shop, ['price_cents' => 2400], ['category_id' => $candles->id]);
    $mug = variant($this->shop, ['price_cents' => 2800]);

    $this->stock->receive($candle, $this->main, 10, 900);
    $this->stock->receive($candle, $this->back, 10, 1100);
    $this->stock->receive($mug, $this->main, 3, 1150);

    $report = app(ValuationReport::class)->summary($this->shop);

    expect($report['total_cents'])->toBe(20 * 1000 + 3 * 1150)
        ->and($report['total_units'])->toBe(23)
        ->and($report['retail_cents'])->toBe(20 * 2400 + 3 * 2800)
        ->and($report['by_location']->firstWhere('name', 'Main store')->value_cents)->toBe(10 * 1000 + 3 * 1150)
        ->and($report['by_location']->firstWhere('name', 'Back room')->value_cents)->toBe(10 * 1000)
        ->and($report['by_category']->firstWhere('name', 'Candles')->units)->toBe(20)
        ->and($report['by_category']->firstWhere('name', 'Uncategorised')->value_cents)->toBe(3450)
        ->and($report['rows']->first()->variant_id)->toBe($candle->id);
});

test('can be limited to one location', function () {
    $variant = variant($this->shop);
    $this->stock->receive($variant, $this->main, 2, 500);
    $this->stock->receive($variant, $this->back, 5, 500);

    $report = app(ValuationReport::class)->summary($this->shop, $this->back->id);

    expect($report['total_units'])->toBe(5)
        ->and($report['total_cents'])->toBe(2500);
});

test('ignores archived products and other shops', function () {
    $archived = variant($this->shop);
    $this->stock->receive($archived, $this->main, 5, 500);
    $archived->product->update(['archived_at' => now()]);

    $other = Shop::factory()->create();
    $this->stock->receive(variant($other), mainLocation($other), 5, 500);

    $report = app(ValuationReport::class)->summary($this->shop);

    expect($report['total_cents'])->toBe(0)
        ->and($report['rows'])->toBeEmpty();
});
