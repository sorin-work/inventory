<?php

use App\Livewire\Products\Form;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Services\StockService;
use Livewire\Livewire;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->actingAs($this->user = owner($this->shop));
});

test('the create page renders', function () {
    $this->get(route('products.create'))->assertOk()->assertSeeLivewire(Form::class);
});

test('creates a simple product with one variant', function () {
    $category = Category::factory()->create(['shop_id' => $this->shop->id]);

    Livewire::test(Form::class)
        ->set('name', 'Brass pen')
        ->set('categoryId', (string) $category->id)
        ->set('variants.0.sku', 'pen-brass')
        ->set('variants.0.barcode', '4006381333931')
        ->set('variants.0.price', '32.50')
        ->set('variants.0.reorder_point', '3')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $product = Product::sole();
    $variant = $product->variants->sole();

    expect($product->name)->toBe('Brass pen')
        ->and($product->shop_id)->toBe($this->shop->id)
        ->and($product->category_id)->toBe($category->id)
        ->and($product->option_names)->toBeNull()
        ->and($variant->sku)->toBe('PEN-BRASS')
        ->and($variant->barcode)->toBe('4006381333931')
        ->and($variant->price_cents)->toBe(3250)
        ->and($variant->reorder_point)->toBe(3)
        ->and($variant->reorder_quantity)->toBeNull();
});

test('generates a variant for each combination of options', function () {
    $component = Livewire::test(Form::class)
        ->set('name', 'Wool beanie')
        ->set('variants.0.price', '32')
        ->call('addOption')
        ->set('options.0.name', 'Size')
        ->set('options.0.values', 'S/M, L/XL')
        ->call('addOption')
        ->set('options.1.name', 'Colour')
        ->set('options.1.values', 'Grey, Navy, grey')
        ->call('generateVariants');

    $variants = $component->get('variants');

    expect($variants)->toHaveCount(4)
        ->and(array_column($variants, 'values'))->toBe([['S/M', 'Grey'], ['S/M', 'Navy'], ['L/XL', 'Grey'], ['L/XL', 'Navy']])
        ->and($variants[0]['sku'])->toBe('WOOL-BEANIE-S-M-GREY')
        ->and($variants[3]['price'])->toBe('32');

    $component->call('save')->assertHasNoErrors();

    $product = Product::sole();
    expect($product->option_names)->toBe(['Size', 'Colour'])
        ->and($product->variants)->toHaveCount(4)
        ->and($product->variants->last()->optionLabel())->toBe('L/XL / Navy');
});

test('needs values for every option before generating', function () {
    Livewire::test(Form::class)
        ->call('addOption')
        ->set('options.0.name', 'Size')
        ->call('generateVariants')
        ->assertHasErrors('options');
});

test('validates the product and its variants', function () {
    variant($this->shop, ['sku' => 'TAKEN', 'barcode' => '96385074']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->set('variants.0.sku', 'taken')
        ->set('variants.0.barcode', '96385074')
        ->set('variants.0.price', '-1')
        ->call('save')
        ->assertHasErrors(['name', 'variants.0.price']);

    Livewire::test(Form::class)
        ->set('name', 'Mug')
        ->set('variants.0.sku', 'taken')
        ->set('variants.0.barcode', '4006381333932')
        ->call('save')
        ->assertHasErrors(['variants.0.sku', 'variants.0.barcode']);

    expect(Product::count())->toBe(1);
});

test('variants on one product need their own skus', function () {
    Livewire::test(Form::class)
        ->set('name', 'Mug')
        ->set('variants.0.sku', 'MUG')
        ->call('addVariant')
        ->set('variants.1.sku', 'mug')
        ->call('save')
        ->assertHasErrors(['variants.0.sku', 'variants.1.sku']);
});

test('edits a product and keeps its variant ids', function () {
    $variant = variant($this->shop, ['sku' => 'MUG-WHITE', 'price_cents' => 2500], ['name' => 'Mug']);

    Livewire::test(Form::class, ['product' => $variant->product])
        ->assertSet('name', 'Mug')
        ->assertSet('variants.0.price', '25.00')
        ->set('name', 'Stoneware mug')
        ->set('variants.0.price', '28')
        ->call('save')
        ->assertHasNoErrors();

    expect($variant->fresh()->price_cents)->toBe(2800)
        ->and($variant->product->fresh()->name)->toBe('Stoneware mug')
        ->and(ProductVariant::count())->toBe(1);
});

test('variants with stock history can not be removed', function () {
    $variant = variant($this->shop, ['sku' => 'MUG-WHITE']);
    $product = $variant->product;
    ProductVariant::factory()->create(['shop_id' => $this->shop->id, 'product_id' => $product->id, 'sku' => 'MUG-GREEN']);
    app(StockService::class)->receive($variant, mainLocation($this->shop), 1, 100);

    Livewire::test(Form::class, ['product' => $product])
        ->call('removeVariant', 0)
        ->assertHasErrors('variants.0.sku')
        ->call('removeVariant', 1)
        ->assertHasNoErrors()
        ->call('save');

    expect($product->variants()->pluck('sku')->all())->toBe(['MUG-WHITE']);
});

test('a category can be created from the form', function () {
    Livewire::test(Form::class)
        ->set('newCategory', 'Candles')
        ->call('createCategory')
        ->assertSet('categoryId', (string) Category::sole()->id);
});

test('suggests a sku from the name', function () {
    Livewire::test(Form::class)
        ->set('name', 'Linen notebook A5')
        ->call('suggestSku', 0)
        ->assertSet('variants.0.sku', 'LINEN-NOTEBOOK-A5');
});
