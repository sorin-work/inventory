<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Exceptions\PurchaseOrderException;
use App\Models\Location;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;
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

    /**
     * Turns a selection from the low stock report into one draft per
     * supplier. Variants whose product has no supplier are returned so the
     * caller can say why they were left out.
     *
     * @param  array<int, int>  $quantities  variant id => quantity
     * @return array{orders: Collection<int, PurchaseOrder>, skipped: Collection<int, ProductVariant>}
     */
    public function draftsFromLowStock(Shop $shop, Location $location, array $quantities, ?User $user = null): array
    {
        $variants = ProductVariant::query()
            ->with('product')
            ->where('shop_id', $shop->id)
            ->whereIn('id', array_keys(array_filter($quantities, fn ($qty) => (int) $qty > 0)))
            ->get();

        [$withSupplier, $skipped] = $variants->partition(fn (ProductVariant $v) => $v->product->supplier_id !== null);

        $orders = $withSupplier
            ->groupBy(fn (ProductVariant $v) => $v->product->supplier_id)
            ->map(function (Collection $group, int $supplierId) use ($shop, $location, $quantities, $user) {
                $lines = $group->map(fn (ProductVariant $v) => [
                    'product_variant_id' => $v->id,
                    'quantity_ordered' => (int) $quantities[$v->id],
                    'unit_cost_cents' => (int) round($v->average_cost),
                ])->values()->all();

                return $this->createDraft($shop, Supplier::findOrFail($supplierId), $location, $lines, [], $user);
            })
            ->values();

        return ['orders' => $orders, 'skipped' => $skipped->values()];
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

        // The same variant twice on one order is almost always a mistake;
        // merge the quantities and keep the first cost.
        $merged = [];

        foreach ($lines as $line) {
            $id = (int) $line['product_variant_id'];

            if (isset($merged[$id])) {
                $merged[$id]['quantity_ordered'] += (int) $line['quantity_ordered'];

                continue;
            }

            $merged[$id] = [
                'product_variant_id' => $id,
                'quantity_ordered' => (int) $line['quantity_ordered'],
                'unit_cost_cents' => (int) $line['unit_cost_cents'],
            ];
        }

        $order->lines()->createMany(array_values($merged));
    }
}
