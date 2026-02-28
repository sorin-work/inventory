<?php

use App\Models\Shop;
use App\Services\StockService;

beforeEach(function () {
    $this->shop = Shop::factory()->create(['name' => 'Harbour Lane']);
    $this->actingAs(owner($this->shop));
});

test('exports products as csv', function () {
    $variant = variant($this->shop, ['sku' => 'MUG-1', 'price_cents' => 2800, 'reorder_point' => 4], ['name' => 'Stoneware mug']);
    app(StockService::class)->receive($variant, mainLocation($this->shop), 3, 1150);

    $response = $this->get(route('products.export'));

    $response->assertOk()->assertDownload('harbour-lane-products-'.now()->format('Y-m-d').'.csv');

    $lines = array_map('str_getcsv', explode("\n", trim($response->streamedContent())));

    expect($lines[0])->toContain('product', 'sku', 'on_hand', 'average_cost')
        ->and(array_combine($lines[0], $lines[1]))->toMatchArray([
            'product' => 'Stoneware mug',
            'sku' => 'MUG-1',
            'price' => '28.00',
            'reorder_point' => '4',
            'on_hand' => '3',
            'average_cost' => '11.50',
        ]);
});

test('escapes cells that would run as spreadsheet formulas', function () {
    variant($this->shop, ['sku' => 'EVIL-1'], ['name' => '=HYPERLINK("http://example.com")']);

    expect($this->get(route('products.export'))->streamedContent())->toContain("'=HYPERLINK");
});
