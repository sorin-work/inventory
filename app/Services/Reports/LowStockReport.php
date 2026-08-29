<?php

namespace App\Services\Reports;

use App\Models\ProductVariant;
use App\Models\PurchaseOrderLine;
use App\Models\Shop;
use Illuminate\Support\Collection;

/**
 * Variants at or below their reorder point. Stock already on open purchase
 * orders counts towards the suggestion so the same shortfall isn't ordered
 * twice.
 */
class LowStockReport
{
    /**
     * @return Collection<int, LowStockRow>
     */
    public function rows(Shop $shop, ?int $categoryId = null, ?int $supplierId = null): Collection
    {
        $variants = ProductVariant::query()
            ->withoutGlobalScopes()
            ->where('product_variants.shop_id', $shop->id)
            ->whereNotNull('reorder_point')
            ->whereHas('product', function ($query) use ($categoryId, $supplierId) {
                $query->withoutGlobalScopes()->whereNull('archived_at');

                if ($categoryId !== null) {
                    $query->where('category_id', $categoryId);
                }

                if ($supplierId !== null) {
                    $query->where('supplier_id', $supplierId);
                }
            })
            ->withOnHand()
            ->with(['product' => fn ($q) => $q->withoutGlobalScopes()->with('supplier', 'category')])
            ->get()
            ->filter(fn (ProductVariant $variant) => $variant->onHand() <= $variant->reorder_point);

        $onOrder = $this->onOrder($variants->pluck('id')->all());

        return $variants
            ->map(fn (ProductVariant $variant) => new LowStockRow(
                variant: $variant,
                onHand: $variant->onHand(),
                onOrder: $onOrder[$variant->id] ?? 0,
            ))
            ->sortBy([
                fn (LowStockRow $a, LowStockRow $b) => $a->onHand - $a->variant->reorder_point <=> $b->onHand - $b->variant->reorder_point,
                fn (LowStockRow $a, LowStockRow $b) => $a->variant->product->name <=> $b->variant->product->name,
            ])
            ->values();
    }

    public function count(Shop $shop): int
    {
        return $this->rows($shop)->count();
    }

    /**
     * @param  list<int>  $variantIds
     * @return array<int, int>
     */
    private function onOrder(array $variantIds): array
    {
        if ($variantIds === []) {
            return [];
        }

        return PurchaseOrderLine::query()
            ->whereIn('product_variant_id', $variantIds)
            ->whereHas('purchaseOrder', fn ($q) => $q->withoutGlobalScopes()->open())
            ->selectRaw('product_variant_id, SUM(quantity_ordered - quantity_received) as outstanding')
            ->groupBy('product_variant_id')
            ->pluck('outstanding', 'product_variant_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();
    }
}
