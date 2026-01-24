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

    /**
     * Books in delivered stock. Quantities are keyed by line id; lines left
     * out or set to zero are skipped. Each received line becomes a receipt
     * movement at the order's location, costed at the line's unit cost.
     *
     * @param  array<int, int>  $quantities
     */
    public function receive(PurchaseOrder $order, array $quantities, ?User $user = null): PurchaseOrder
    {
        if (! $order->status->canReceive()) {
            throw PurchaseOrderException::cannot('received', $order->status);
        }

        $quantities = array_filter(array_map('intval', $quantities), fn (int $qty) => $qty !== 0);

        if ($quantities === []) {
            throw PurchaseOrderException::nothingToReceive();
        }

        return DB::transaction(function () use ($order, $quantities, $user) {
            $order = PurchaseOrder::withoutGlobalScopes()->lockForUpdate()->findOrFail($order->id);
            $order->load('lines.variant.product', 'location');

            foreach ($quantities as $lineId => $quantity) {
                $line = $order->lines->firstWhere('id', $lineId);

                if ($line === null) {
                    continue;
                }

                if ($quantity < 0 || $quantity > $line->remaining()) {
                    throw PurchaseOrderException::overReceived($line->variant->sku, $line->remaining());
                }

                $this->stock->receive(
                    $line->variant,
                    $order->location,
                    $quantity,
                    $line->unit_cost_cents,
                    $user,
                    $order->number,
                    $order,
                );

                $line->increment('quantity_received', $quantity);
            }

            $complete = $order->lines->every(fn ($line) => $line->isFullyReceived());

            $order->forceFill([
                'status' => $complete ? PurchaseOrderStatus::Received : PurchaseOrderStatus::PartiallyReceived,
                'received_at' => $complete ? now() : null,
            ])->save();

            return $order;
        });
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
        $last = PurchaseOrder::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('number');

        $next = $last === null ? 1 : ((int) preg_replace('/\D/', '', $last)) + 1;

        return sprintf('PO-%04d', $next);
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
