<?php

use App\Livewire\Products\Import;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Services\Csv\ProductImporter;
use App\Services\StockService;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

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

test('an export can be imported again without changes', function () {
    variant($this->shop, ['sku' => 'MUG-1', 'price_cents' => 2800], ['name' => '=Odd name', 'option_names' => ['Glaze']]);
    $csv = $this->get(route('products.export'))->streamedContent();

    $result = app(ProductImporter::class)->import($this->shop, csvFile($csv));

    expect($result->errors)->toBe([])
        ->and($result->updated)->toBe(1)
        ->and(ProductVariant::sole()->product->name)->toBe('=Odd name');
});

test('uploads a file and checks it before importing', function () {
    $file = UploadedFile::fake()->createWithContent('products.csv', "product,sku,price\nMug,MUG-1,28\n");

    $component = Livewire::test(Import::class)
        ->set('file', $file)
        ->call('import')
        ->assertHasNoErrors()
        ->assertSee('The file looks good');

    expect($component->get('result')['created'])->toBe(1)
        ->and(ProductVariant::count())->toBe(0);

    $component->set('dryRun', false)->call('import')->assertSee('Import finished');

    expect(ProductVariant::sole()->sku)->toBe('MUG-1');
});

test('only accepts csv files', function () {
    Livewire::test(Import::class)
        ->set('file', UploadedFile::fake()->create('photo.png', 20, 'image/png'))
        ->call('import')
        ->assertHasErrors('file');
});
