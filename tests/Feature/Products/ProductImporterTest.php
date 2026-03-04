<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Services\Csv\ProductImporter;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->importer = app(ProductImporter::class);
});

function import(string $csv, bool $dryRun = false)
{
    return test()->importer->import(test()->shop, csvFile($csv), $dryRun);
}

test('creates products, grouping variants by product name', function () {
    $result = import(<<<'CSV'
        product,sku,barcode,option1_name,option1_value,category,supplier,price,reorder_point,reorder_quantity
        Linen tea towel,TOWEL-OAT,4006381333931,Colour,Oat,Textiles,Northwind,16.00,8,20
        Linen tea towel,towel-sage,,Colour,Sage,Textiles,Northwind,16,8,20
        Brass pen,PEN-BRASS,,,,Stationery,,32.5,,
        CSV);

    expect($result->hasErrors())->toBeFalse()
        ->and($result->created)->toBe(3)
        ->and($result->productsCreated)->toBe(2)
        ->and($result->categoriesCreated)->toBe(['Textiles', 'Stationery'])
        ->and($result->suppliersCreated)->toBe(['Northwind']);

    $towel = Product::withoutGlobalScopes()->where('name', 'Linen tea towel')->firstOrFail();
    expect($towel->option_names)->toBe(['Colour'])
        ->and($towel->variants->pluck('sku')->all())->toBe(['TOWEL-OAT', 'TOWEL-SAGE'])
        ->and($towel->variants->first()->option_values)->toBe(['Oat'])
        ->and($towel->variants->first()->price_cents)->toBe(1600)
        ->and($towel->variants->first()->reorder_point)->toBe(8)
        ->and($towel->supplier->name)->toBe('Northwind');

    $pen = ProductVariant::withoutGlobalScopes()->where('sku', 'PEN-BRASS')->firstOrFail();
    expect($pen->price_cents)->toBe(3250)
        ->and($pen->reorder_point)->toBeNull();
});

test('updates existing variants matched by sku', function () {
    $variant = variant($this->shop, ['sku' => 'MUG-WHITE', 'price_cents' => 2500]);

    $result = import("product,sku,price,reorder_point\nStoneware mug,mug-white,28.00,6\n");

    expect($result->updated)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and($variant->fresh()->price_cents)->toBe(2800)
        ->and($variant->fresh()->reorder_point)->toBe(6)
        ->and($variant->product->fresh()->name)->toBe('Stoneware mug');
});

test('adds new skus to an existing product with the same name', function () {
    $variant = variant($this->shop, ['sku' => 'MUG-WHITE'], ['name' => 'Stoneware mug']);

    import("product,sku\nstoneware MUG,MUG-GREEN\n");

    expect($variant->product->variants()->pluck('sku')->all())->toBe(['MUG-WHITE', 'MUG-GREEN']);
});

test('reports every problem with its line number and saves nothing', function () {
    variant($this->shop, ['sku' => 'TAKEN', 'barcode' => '96385074']);

    $result = import(<<<'CSV'
        product,sku,barcode,price,reorder_point,unit,option1_value
        ,NO-NAME,,,,,
        Bad sku,HAS SPACE/SLASH,,,,,
        Wrong check digit,GOOD-1,4006381333932,,,,
        Duplicate,GOOD-2,,abc,-1,crate,
        Duplicate again,GOOD-2,,,,,
        Stolen barcode,GOOD-3,96385074,,,,
        Orphan option,GOOD-4,,,,,Blue
        Fine,GOOD-5,,,,,
        CSV);

    expect($result->errors)->toHaveKeys([2, 3, 4, 5, 6, 7, 8])
        ->and($result->errors)->not->toHaveKey(9)
        ->and($result->errors[2])->toContain('Product name is required.')
        ->and($result->errors[4][0])->toContain('not valid')
        ->and($result->errors[5])->toHaveCount(3)
        ->and($result->errors[6][0])->toContain('also used on line 5')
        ->and($result->errors[7][0])->toContain('already belongs to TAKEN')
        ->and($result->errors[8][0])->toContain('no name')
        ->and(ProductVariant::withoutGlobalScopes()->count())->toBe(1);
});

test('requires the product and sku columns', function () {
    $result = import("product,price\nMug,10\n");

    expect($result->errors[1][0])->toBe('Missing required column: sku.');
});

test('a dry run reports what would happen without saving', function () {
    $result = import("product,sku,category\nMug,MUG-1,Ceramics\nBowl,BOWL-1,Ceramics\n", dryRun: true);

    expect($result->dryRun)->toBeTrue()
        ->and($result->created)->toBe(2)
        ->and($result->categoriesCreated)->toBe(['Ceramics'])
        ->and(Product::withoutGlobalScopes()->count())->toBe(0)
        ->and(Category::withoutGlobalScopes()->count())->toBe(0);
});

test('turns escaped formula cells back into text', function () {
    import("product,sku\n'=Fancy mug,MUG-EQ\n");

    expect(Product::withoutGlobalScopes()->firstOrFail()->name)->toBe('=Fancy mug');
});

test('keeps variant options when a row leaves them out', function () {
    $variant = variant($this->shop, ['sku' => 'MUG-GREEN', 'option_values' => ['Sea green']], ['name' => 'Mug', 'option_names' => ['Glaze']]);

    import("product,sku,price\nMug,MUG-GREEN,30\n");

    expect($variant->fresh()->option_values)->toBe(['Sea green'])
        ->and($variant->fresh()->price_cents)->toBe(3000);
});
