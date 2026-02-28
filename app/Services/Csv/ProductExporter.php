<?php

namespace App\Services\Csv;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Support\CsvCell;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExporter
{
    public function download(Shop $shop): StreamedResponse
    {
        $filename = Str::slug($shop->name).'-products-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($shop) {
            $out = fopen('php://output', 'w');
            $this->write($out, $shop);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  resource  $out
     */
    public function write($out, Shop $shop): void
    {
        fputcsv($out, ProductCsv::COLUMNS, escape: '');

        Product::query()
            ->withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->whereNull('archived_at')
            ->with([
                'category',
                'supplier',
                'variants' => fn ($q) => $q->withoutGlobalScopes()->withOnHand(),
            ])
            ->orderBy('name')
            ->chunk(200, function ($products) use ($out) {
                foreach ($products as $product) {
                    foreach ($product->variants as $variant) {
                        fputcsv($out, array_map(CsvCell::escape(...), $this->row($product, $variant)), escape: '');
                    }
                }
            });
    }

    /**
     * @return list<string|int|null>
     */
    private function row(Product $product, ProductVariant $variant): array
    {
        $names = array_values($product->option_names ?? []);
        $values = array_values($variant->option_values ?? []);
        $options = [];

        for ($i = 0; $i < ProductCsv::MAX_OPTIONS; $i++) {
            $options[] = $names[$i] ?? '';
            $options[] = $values[$i] ?? '';
        }

        return [
            $product->name,
            $variant->sku,
            $variant->barcode,
            ...$options,
            $product->category?->name,
            $product->supplier?->name,
            $product->unit,
            number_format($variant->price_cents / 100, 2, '.', ''),
            $variant->reorder_point,
            $variant->reorder_quantity,
            $product->description,
            $variant->onHand(),
            number_format($variant->average_cost / 100, 2, '.', ''),
        ];
    }
}
