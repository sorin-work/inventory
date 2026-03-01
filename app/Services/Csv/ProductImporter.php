<?php

namespace App\Services\Csv;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Supplier;
use App\Support\Barcode;
use App\Support\CsvCell;
use App\Support\Money;
use App\Support\Sku;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Creates and updates products from a CSV file. Rows are matched to
 * existing variants by SKU; new SKUs are added to the product with the same
 * name, or to a new product. Stock quantities are never imported: stock
 * only changes through movements so the ledger stays complete.
 */
class ProductImporter
{
    /** @var array<string, Category> */
    private array $categories = [];

    /** @var array<string, Supplier> */
    private array $suppliers = [];

    /** @var array<string, Product> */
    private array $products = [];

    public function import(Shop $shop, string $path, bool $dryRun = false): ImportResult
    {
        $result = new ImportResult($dryRun);

        try {
            $csv = CsvReader::read($path);
        } catch (RuntimeException $e) {
            $result->addError(1, $e->getMessage());

            return $result;
        }

        $missing = array_diff(ProductCsv::REQUIRED, $csv['headers']);

        if ($missing !== []) {
            $result->addError(1, 'Missing required column: '.implode(', ', $missing).'.');

            return $result;
        }

        $rows = $this->validate($shop, $csv['rows'], $result);

        if ($result->hasErrors()) {
            return $result;
        }

        $this->reset();

        try {
            DB::transaction(function () use ($shop, $rows, $result, $dryRun) {
                foreach ($rows as $row) {
                    $this->apply($shop, $row, $result);
                }

                if ($dryRun) {
                    throw new DryRunFinished;
                }
            });
        } catch (DryRunFinished) {
            // Rolled back on purpose; the result describes what would happen.
        } catch (Throwable $e) {
            report($e);
            $result->addError(0, 'The import failed and nothing was saved. '.$e->getMessage());
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return list<array<string, mixed>>
     */
    private function validate(Shop $shop, array $rows, ImportResult $result): array
    {
        $valid = [];
        $skus = [];
        $barcodes = [];

        foreach ($rows as $line => $raw) {
            $raw = array_map(CsvCell::unescape(...), $raw);
            $errors = [];

            $name = $raw['product'] ?? '';
            $sku = Sku::normalize($raw['sku'] ?? '');
            $barcode = Barcode::normalize($raw['barcode'] ?? null);

            if ($name === '') {
                $errors[] = 'Product name is required.';
            } elseif (mb_strlen($name) > 255) {
                $errors[] = 'Product name is longer than 255 characters.';
            }

            if ($sku === '') {
                $errors[] = 'SKU is required.';
            } elseif (! Sku::isValid($sku)) {
                $errors[] = "SKU \"{$sku}\" may only contain letters, numbers, dashes, dots and underscores.";
            } elseif (isset($skus[$sku])) {
                $errors[] = "SKU {$sku} is also used on line {$skus[$sku]}.";
            } else {
                $skus[$sku] = $line;
            }

            if ($barcode !== null) {
                if (! Barcode::isValid($barcode)) {
                    $errors[] = "Barcode {$barcode} is not valid.";
                } elseif (isset($barcodes[$barcode])) {
                    $errors[] = "Barcode {$barcode} is also used on line {$barcodes[$barcode]}.";
                } else {
                    $barcodes[$barcode] = $line;

                    $owner = ProductVariant::withoutGlobalScopes()
                        ->where('shop_id', $shop->id)
                        ->where('barcode', $barcode)
                        ->value('sku');

                    if ($owner !== null && $owner !== $sku) {
                        $errors[] = "Barcode {$barcode} already belongs to {$owner}.";
                    }
                }
            }

            $price = null;

            if (($raw['price'] ?? '') !== '') {
                try {
                    $price = Money::parse($raw['price']);

                    if ($price < 0) {
                        $errors[] = 'Price cannot be negative.';
                    }
                } catch (InvalidArgumentException) {
                    $errors[] = "Price \"{$raw['price']}\" is not a number.";
                }
            }

            $reorderPoint = $this->wholeNumber($raw['reorder_point'] ?? '', 'Reorder point', 0, $errors);
            $reorderQuantity = $this->wholeNumber($raw['reorder_quantity'] ?? '', 'Reorder quantity', 1, $errors);

            $unit = strtolower($raw['unit'] ?? '');

            if ($unit !== '' && ! in_array($unit, Product::UNITS, true)) {
                $errors[] = "Unit \"{$unit}\" is not one of: ".implode(', ', Product::UNITS).'.';
            }

            $options = [];

            for ($i = 1; $i <= ProductCsv::MAX_OPTIONS; $i++) {
                $optionName = $raw["option{$i}_name"] ?? '';
                $optionValue = $raw["option{$i}_value"] ?? '';

                if ($optionValue !== '' && $optionName === '') {
                    $errors[] = "Option {$i} has a value but no name.";
                }

                if ($optionName !== '') {
                    $options[$optionName] = $optionValue;
                }
            }

            foreach ($errors as $error) {
                $result->addError($line, $error);
            }

            if ($errors === []) {
                $valid[] = [
                    'line' => $line,
                    'name' => $name,
                    'sku' => $sku,
                    'barcode' => $barcode,
                    'price_cents' => $price,
                    'reorder_point' => $reorderPoint,
                    'reorder_quantity' => $reorderQuantity,
                    'unit' => $unit === '' ? null : $unit,
                    'category' => $raw['category'] ?? '',
                    'supplier' => $raw['supplier'] ?? '',
                    'description' => $raw['description'] ?? '',
                    'options' => $options,
                ];
            }
        }

        return $valid;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function apply(Shop $shop, array $row, ImportResult $result): void
    {
        $variant = ProductVariant::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('sku', $row['sku'])
            ->first();

        $productAttributes = array_filter([
            'category_id' => $row['category'] !== '' ? $this->category($shop, $row['category'], $result)->id : null,
            'supplier_id' => $row['supplier'] !== '' ? $this->supplier($shop, $row['supplier'], $result)->id : null,
            'unit' => $row['unit'],
            'description' => $row['description'] !== '' ? $row['description'] : null,
        ], fn ($value) => $value !== null);

        if ($variant !== null) {
            $product = Product::withoutGlobalScopes()->findOrFail($variant->product_id);
            $product->fill(['name' => $row['name'], ...$productAttributes]);
            $this->mergeOptionNames($product, $row['options']);
            $product->save();

            $variant->fill(array_filter([
                'barcode' => $row['barcode'],
                'price_cents' => $row['price_cents'],
                'reorder_point' => $row['reorder_point'],
                'reorder_quantity' => $row['reorder_quantity'],
            ], fn ($value) => $value !== null));
            $variant->option_values = $this->optionValues($product, $row['options']) ?: $variant->option_values;
            $variant->save();

            $this->products[Str::lower($product->name)] = $product;
            $result->updated++;

            return;
        }

        $product = $this->product($shop, $row['name'], $productAttributes, $result);
        $this->mergeOptionNames($product, $row['options']);
        $product->fill($productAttributes)->save();

        ProductVariant::create([
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'sku' => $row['sku'],
            'barcode' => $row['barcode'],
            'option_values' => $this->optionValues($product, $row['options']),
            'price_cents' => $row['price_cents'] ?? 0,
            'reorder_point' => $row['reorder_point'],
            'reorder_quantity' => $row['reorder_quantity'],
            'position' => ProductVariant::withoutGlobalScopes()->where('product_id', $product->id)->count(),
        ]);

        $result->created++;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(Shop $shop, string $name, array $attributes, ImportResult $result): Product
    {
        $key = Str::lower($name);

        if (isset($this->products[$key])) {
            return $this->products[$key];
        }

        $product = Product::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->whereRaw('LOWER(name) = ?', [$key])
            ->orderBy('archived_at')
            ->first();

        if ($product === null) {
            $product = Product::create(['shop_id' => $shop->id, 'name' => $name, ...$attributes]);
            $result->productsCreated++;
        }

        return $this->products[$key] = $product;
    }

    private function category(Shop $shop, string $name, ImportResult $result): Category
    {
        $key = Str::lower($name);

        return $this->categories[$key] ??= Category::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->whereRaw('LOWER(name) = ?', [$key])
            ->first()
            ?? tap(Category::create(['shop_id' => $shop->id, 'name' => $name]), function () use ($result, $name) {
                $result->categoriesCreated[] = $name;
            });
    }

    private function supplier(Shop $shop, string $name, ImportResult $result): Supplier
    {
        $key = Str::lower($name);

        return $this->suppliers[$key] ??= Supplier::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->whereRaw('LOWER(name) = ?', [$key])
            ->first()
            ?? tap(Supplier::create(['shop_id' => $shop->id, 'name' => $name]), function () use ($result, $name) {
                $result->suppliersCreated[] = $name;
            });
    }

    /**
     * @param  array<string, string>  $options
     */
    private function mergeOptionNames(Product $product, array $options): void
    {
        $names = $product->option_names ?? [];

        foreach (array_keys($options) as $name) {
            $exists = collect($names)->contains(fn ($existing) => Str::lower($existing) === Str::lower($name));

            if (! $exists && count($names) < ProductCsv::MAX_OPTIONS) {
                $names[] = $name;
            }
        }

        $product->option_names = $names === [] ? null : array_values($names);
    }

    /**
     * Lines the row's option values up with the product's option names.
     *
     * @param  array<string, string>  $options
     * @return list<string>
     */
    private function optionValues(Product $product, array $options): array
    {
        $byName = [];

        foreach ($options as $name => $value) {
            $byName[Str::lower($name)] = $value;
        }

        return array_map(fn ($name) => $byName[Str::lower($name)] ?? '', $product->option_names ?? []);
    }

    /**
     * @param  list<string>  $errors
     */
    private function wholeNumber(string $value, string $label, int $min, array &$errors): ?int
    {
        if ($value === '') {
            return null;
        }

        if (! preg_match('/^\d+$/', $value) || (int) $value < $min) {
            $errors[] = "{$label} must be a whole number of at least {$min}.";

            return null;
        }

        return (int) $value;
    }

    private function reset(): void
    {
        $this->categories = [];
        $this->suppliers = [];
        $this->products = [];
    }
}
