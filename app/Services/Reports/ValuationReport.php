<?php

namespace App\Services\Reports;

use App\Models\Shop;
use App\Support\AverageCost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Stock value at weighted average cost. Only positive stock counts; a
 * location that has gone negative through a mistake shouldn't make the
 * shop look poorer than it is.
 */
class ValuationReport
{
    /**
     * @return array{
     *     total_cents: int,
     *     total_units: int,
     *     by_location: Collection<int, object>,
     *     by_category: Collection<int, object>,
     *     rows: Collection<int, object>,
     * }
     */
    public function summary(Shop $shop, ?int $locationId = null): array
    {
        $levels = DB::table('stock_levels')
            ->join('product_variants', 'product_variants.id', '=', 'stock_levels.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('locations', 'locations.id', '=', 'stock_levels.location_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('product_variants.shop_id', $shop->id)
            ->whereNull('products.archived_at')
            ->where('stock_levels.quantity', '>', 0)
            ->when($locationId, fn ($q) => $q->where('stock_levels.location_id', $locationId))
            ->select([
                'stock_levels.quantity',
                'stock_levels.location_id',
                'locations.name as location_name',
                'product_variants.id as variant_id',
                'product_variants.sku',
                'product_variants.option_values',
                'product_variants.average_cost',
                'product_variants.price_cents',
                'products.id as product_id',
                'products.name as product_name',
                'categories.name as category_name',
            ])
            ->get()
            ->map(function (object $row) {
                $row->quantity = (int) $row->quantity;
                $row->average_cost = (float) $row->average_cost;
                $row->value_cents = AverageCost::value($row->quantity, $row->average_cost);
                $row->category_name ??= 'Uncategorised';

                return $row;
            });

        $group = fn (string $key) => $levels
            ->groupBy($key)
            ->map(fn (Collection $rows, string $name) => (object) [
                'name' => $name,
                'units' => $rows->sum('quantity'),
                'value_cents' => $rows->sum('value_cents'),
            ])
            ->sortByDesc('value_cents')
            ->values();

        $rows = $levels
            ->groupBy('variant_id')
            ->map(function (Collection $rows) {
                $first = $rows->first();
                $options = implode(' / ', array_filter(json_decode($first->option_values ?? '[]', true) ?: []));

                return (object) [
                    'variant_id' => $first->variant_id,
                    'product_id' => $first->product_id,
                    'name' => $options === '' ? $first->product_name : $first->product_name.' — '.$options,
                    'sku' => $first->sku,
                    'category' => $first->category_name,
                    'units' => $rows->sum('quantity'),
                    'average_cost' => $first->average_cost,
                    'value_cents' => $rows->sum('value_cents'),
                ];
            })
            ->sortByDesc('value_cents')
            ->values();

        return [
            'total_cents' => $levels->sum('value_cents'),
            'total_units' => $levels->sum('quantity'),
            'by_location' => $group('location_name'),
            'by_category' => $group('category_name'),
            'rows' => $rows,
        ];
    }
}
