<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Exceptions\PurchaseOrderException;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * @param  list<array{product_variant_id: int, quantity_ordered: int, unit_cost_cents: int}>  $lines
     * @param  array{expected_on?: ?string, notes?: ?string}  $details
     */
    public function createDraft(
        Shop $shop,
        Supplier $supplier,
        Location $location,
        array $lines,
        array $details = [],
        ?User $user = null,
    ): PurchaseOrder {
        return DB::transaction(function () use ($shop, $supplier, $location, $lines, $details, $user) {
            $order = PurchaseOrder::create([
                'shop_id' => $shop->id,
                'supplier_id' => $supplier->id,
                'location_id' => $location->id,
                'user_id' => $user?->id,
                'number' => $this->nextNumber($shop),
                'status' => PurchaseOrderStatus::Draft,
                'expected_on' => $details['expected_on'] ?? null,
                'notes' => $details['notes'] ?? null,
            ]);

            $this->replaceLines($order, $lines);

            return $order->load('lines');
        });
    }

    /**
     * @param  list<array{product_variant_id: int, quantity_ordered: int, unit_cost_cents: int}>  $lines
     * @param  array{supplier_id?: int, location_id?: int, expected_on?: ?string, notes?: ?string}  $details
     */
    public function updateDraft(PurchaseOrder $order, array $lines, array $details = []): PurchaseOrder
    {
        if (! $order->status->isEditable()) {
            throw PurchaseOrderException::cannot('edited', $order->status);
        }

        return DB::transaction(function () use ($order, $lines, $details) {
            $order->fill($details)->save();
            $this->replaceLines($order, $lines);

            return $order->load('lines');
        });
    }

    public function markOrdered(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status !== PurchaseOrderStatus::Draft) {
            throw PurchaseOrderException::cannot('placed', $order->status);
        }

        if ($order->lines()->count() === 0) {
            throw PurchaseOrderException::noLines();
        }

        $order->forceFill([
            'status' => PurchaseOrderStatus::Ordered,
            'ordered_at' => now(),
        ])->save();

        return $order;
    }

    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        if (! in_array($order->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Ordered], true)) {
            throw PurchaseOrderException::cannot('cancelled', $order->status);
        }

        if ($order->hasReceipts()) {
            throw PurchaseOrderException::alreadyReceiving();
        }

        $order->forceFill(['status' => PurchaseOrderStatus::Cancelled])->save();

        return $order;
    }

    public function nextNumber(Shop $shop): string
    {
        $count = PurchaseOrder::withoutGlobalScopes()->where('shop_id', $shop->id)->count();

        return sprintf('PO-%04d', $count + 1);
    }

    /**
     * @param  list<array{product_variant_id: int, quantity_ordered: int, unit_cost_cents: int}>  $lines
     */
    private function replaceLines(PurchaseOrder $order, array $lines): void
    {
        $order->lines()->delete();
        $order->lines()->createMany($lines);
    }
}
