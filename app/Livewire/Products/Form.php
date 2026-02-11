<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\WithShop;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Support\Barcode;
use App\Support\Money;
use App\Support\Sku;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    use WithShop;

    public const MAX_OPTIONS = 3;

    public const MAX_VARIANTS = 100;

    public ?Product $product = null;

    public string $name = '';

    public string $description = '';

    public string $categoryId = '';

    public string $supplierId = '';

    public string $unit = 'each';

    /** @var list<array{name: string, values: string}> */
    public array $options = [];

    /**
     * @var list<array{id: ?int, values: list<string>, sku: string, barcode: string, price: string, reorder_point: string, reorder_quantity: string}>
     */
    public array $variants = [];

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->product = $product;
            $this->name = $product->name;
            $this->description = (string) $product->description;
            $this->categoryId = (string) $product->category_id;
            $this->supplierId = (string) $product->supplier_id;
            $this->unit = $product->unit;

            $names = $product->option_names ?? [];
            $product->load('variants');

            $this->options = array_map(fn ($name, $i) => [
                'name' => $name,
                'values' => $product->variants->pluck("option_values.{$i}")->filter()->unique()->implode(', '),
            ], $names, array_keys($names));

            $this->variants = $product->variants->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'values' => array_values($v->option_values ?? []),
                'sku' => $v->sku,
                'barcode' => (string) $v->barcode,
                'price' => number_format($v->price_cents / 100, 2, '.', ''),
                'reorder_point' => (string) $v->reorder_point,
                'reorder_quantity' => (string) $v->reorder_quantity,
            ])->all();

            return;
        }

        $this->product = null;
        $this->variants = [$this->blankVariant()];
    }

    public function addOption(): void
    {
        if (count($this->options) >= self::MAX_OPTIONS) {
            return;
        }

        $this->options[] = ['name' => '', 'values' => ''];

        foreach ($this->variants as $i => $variant) {
            $this->variants[$i]['values'][] = '';
        }
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);

        foreach ($this->variants as $i => $variant) {
            $values = $variant['values'];
            unset($values[$index]);
            $this->variants[$i]['values'] = array_values($values);
        }
    }

    /**
     * Builds one variant for every combination of option values, keeping the
     * rows (and their SKUs) that already exist.
     */
    public function generateVariants(): void
    {
        $lists = array_map(fn ($option) => $this->splitValues($option['values']), $this->options);

        if ($lists === [] || in_array([], $lists, true)) {
            $this->addError('options', 'Give every option at least one value, separated by commas.');

            return;
        }

        $combinations = [[]];

        foreach ($lists as $values) {
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $next[] = [...$combination, $value];
                }
            }

            $combinations = $next;
        }

        $existing = collect($this->variants)->keyBy(fn ($v) => Str::lower(implode('|', $v['values'])));
        $template = $this->variants[0] ?? $this->blankVariant();

        $this->variants = array_map(function (array $values) use ($existing, $template) {
            $key = Str::lower(implode('|', $values));

            return $existing[$key] ?? [
                ...$this->blankVariant(),
                'values' => $values,
                'sku' => $this->name !== '' ? Sku::suggest($this->name, $values) : '',
                'price' => $template['price'],
                'reorder_point' => $template['reorder_point'],
                'reorder_quantity' => $template['reorder_quantity'],
            ];
        }, $combinations);

        $this->resetErrorBag('options');
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            ...$this->blankVariant(),
            'values' => array_fill(0, count($this->options), ''),
        ];
    }

    public function removeVariant(int $index): void
    {
        $this->resetErrorBag();

        $id = $this->variants[$index]['id'] ?? null;

        if ($id !== null && ! $this->canDeleteVariant($id)) {
            $this->addError("variants.{$index}.sku", 'This variant has stock history or purchase orders and cannot be removed. Archive the product instead.');

            return;
        }

        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function suggestSku(int $index): void
    {
        if ($this->name === '') {
            return;
        }

        $this->variants[$index]['sku'] = Sku::suggest($this->name, $this->variants[$index]['values'] ?? []);
    }

    public function save(): void
    {
        foreach ($this->variants as $i => $variant) {
            $this->variants[$i]['sku'] = Sku::normalize($variant['sku']);
            $this->variants[$i]['barcode'] = (string) Barcode::normalize($variant['barcode']);
        }

        $this->validate($this->rules(), $this->messages(), $this->attributes());
        $this->validateVariantCodes();

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $product = DB::transaction(function () {
            $product = $this->product ?? new Product;
            $product->fill([
                'name' => trim($this->name),
                'description' => trim($this->description) ?: null,
                'category_id' => $this->categoryId ?: null,
                'supplier_id' => $this->supplierId ?: null,
                'unit' => $this->unit,
                'option_names' => $this->options === [] ? null : array_map(fn ($o) => trim($o['name']), $this->options),
            ])->save();

            $keep = [];

            foreach ($this->variants as $position => $row) {
                $variant = $row['id'] !== null
                    ? $product->variants()->findOrFail($row['id'])
                    : new ProductVariant(['product_id' => $product->id]);

                $variant->fill([
                    'sku' => $row['sku'],
                    'barcode' => $row['barcode'] === '' ? null : $row['barcode'],
                    'option_values' => $this->options === [] ? null : array_map('trim', $row['values']),
                    'price_cents' => $row['price'] === '' ? 0 : Money::parse($row['price']),
                    'reorder_point' => $row['reorder_point'] === '' ? null : (int) $row['reorder_point'],
                    'reorder_quantity' => $row['reorder_quantity'] === '' ? null : (int) $row['reorder_quantity'],
                    'position' => $position,
                ])->save();

                $keep[] = $variant->id;
            }

            // Rows removed from the form. removeVariant() already refused the
            // ones with history, so these are safe to delete.
            $product->variants()->whereNotIn('id', $keep)->get()
                ->filter(fn (ProductVariant $v) => $this->canDeleteVariant($v->id))
                ->each->delete();

            return $product;
        });

        session()->flash('status', $this->product ? 'Product saved.' : 'Product created.');

        $this->redirectRoute('products.show', $product, navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $shopId = $this->shop()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'categoryId' => ['nullable', Rule::exists('categories', 'id')->where('shop_id', $shopId)],
            'supplierId' => ['nullable', Rule::exists('suppliers', 'id')->where('shop_id', $shopId)],
            'unit' => ['required', Rule::in(Product::UNITS)],
            'options' => ['array', 'max:'.self::MAX_OPTIONS],
            'options.*.name' => ['required', 'string', 'max:40', 'distinct:ignore_case'],
            'variants' => ['required', 'array', 'min:1', 'max:'.self::MAX_VARIANTS],
            'variants.*.values' => ['array'],
            'variants.*.values.*' => [$this->options === [] ? 'nullable' : 'required', 'string', 'max:60'],
            'variants.*.sku' => ['required', 'string', 'max:64', 'regex:'.Sku::PATTERN, 'distinct'],
            'variants.*.barcode' => ['nullable', 'string', 'max:48', 'distinct'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'variants.*.reorder_point' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'variants.*.reorder_quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'variants.*.sku.regex' => 'Use letters, numbers, dashes, dots and underscores.',
            'variants.*.sku.distinct' => 'Each variant needs its own SKU.',
            'variants.*.barcode.distinct' => 'Each variant needs its own barcode.',
            'variants.*.values.*.required' => 'Fill in every option.',
            'options.*.name.required' => 'Name the option, e.g. Size or Colour.',
            'options.*.name.distinct' => 'Options need different names.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function attributes(): array
    {
        return [
            'categoryId' => 'category',
            'supplierId' => 'supplier',
            'variants.*.sku' => 'SKU',
            'variants.*.barcode' => 'barcode',
            'variants.*.price' => 'price',
            'variants.*.reorder_point' => 'reorder point',
            'variants.*.reorder_quantity' => 'reorder quantity',
        ];
    }

    /**
     * SKUs and barcodes must be unique across the shop, not only within
     * this form.
     */
    protected function validateVariantCodes(): void
    {
        $ownIds = array_filter(array_column($this->variants, 'id'));

        $takenSkus = ProductVariant::query()
            ->whereIn('sku', array_column($this->variants, 'sku'))
            ->whereNotIn('id', $ownIds)
            ->pluck('sku')
            ->all();

        $barcodes = array_filter(array_column($this->variants, 'barcode'));
        $takenBarcodes = $barcodes === [] ? [] : ProductVariant::query()
            ->whereIn('barcode', $barcodes)
            ->whereNotIn('id', $ownIds)
            ->pluck('barcode')
            ->all();

        foreach ($this->variants as $i => $variant) {
            if (in_array($variant['sku'], $takenSkus, true)) {
                $this->addError("variants.{$i}.sku", "SKU {$variant['sku']} is already used by another product.");
            }

            if ($variant['barcode'] === '') {
                continue;
            }

            if (! Barcode::isValid($variant['barcode'])) {
                $this->addError("variants.{$i}.barcode", 'This barcode is not valid. Check the last digit.');
            } elseif (in_array($variant['barcode'], $takenBarcodes, true)) {
                $this->addError("variants.{$i}.barcode", 'This barcode is already used by another product.');
            }
        }
    }

    protected function canDeleteVariant(int $id): bool
    {
        $variant = ProductVariant::query()->find($id);

        if ($variant === null) {
            return true;
        }

        return ! $variant->movements()->exists()
            && ! DB::table('purchase_order_lines')->where('product_variant_id', $id)->exists();
    }

    /**
     * @return list<string>
     */
    protected function splitValues(string $values): array
    {
        return collect(preg_split('/\s*,\s*/', trim($values)))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->unique(fn ($v) => Str::lower($v))
            ->values()
            ->all();
    }

    /**
     * @return array{id: null, values: list<string>, sku: string, barcode: string, price: string, reorder_point: string, reorder_quantity: string}
     */
    protected function blankVariant(): array
    {
        return [
            'id' => null,
            'values' => [],
            'sku' => '',
            'barcode' => '',
            'price' => '',
            'reorder_point' => '',
            'reorder_quantity' => '',
        ];
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Supplier>
     */
    public function suppliers(): Collection
    {
        return Supplier::query()->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.products.form')
            ->title($this->product ? 'Edit '.$this->product->name : 'New product');
    }
}
