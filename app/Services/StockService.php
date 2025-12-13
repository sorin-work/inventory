<?php

namespace App\Services;

use App\Enums\AdjustmentReason;
use App\Enums\MovementType;
use App\Exceptions\StockException;
use App\Models\Location;
use App\Models\ProductVariant;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\AverageCost;
use Illuminate\Support\Facades\DB;

/**
 * Every change to stock goes through here so that stock levels, the ledger
 * and the average cost always move together inside one transaction.
 */
class StockService
{
    public function receive(
        ProductVariant $variant,
        Location $location,
        int $quantity,
        float $unitCost,
        ?User $user = null,
        ?string $note = null,
    ): StockMovement {
        $this->guard($variant, $location, $quantity);

        return DB::transaction(function () use ($variant, $location, $quantity, $unitCost, $user, $note) {
            $locked = $this->lockVariant($variant);
            $level = $this->lockLevel($locked, $location);

            $locked->average_cost = AverageCost::afterReceipt($level->quantity, $locked->average_cost, $quantity, $unitCost);
            $locked->save();
            $variant->average_cost = $locked->average_cost;

            $level->quantity += $quantity;
            $level->save();

            return $this->record($locked, $location, MovementType::Receipt, $quantity, $level->quantity, [
                'user_id' => $user?->id,
                'unit_cost' => $unitCost,
                'note' => $note,
            ]);
        });
    }

    /**
     * Adds or removes stock for a reason other than buying or selling.
     * Stock added back (returns, found items) is valued at the current
     * average cost, so the average doesn't move.
     */
    public function adjust(
        ProductVariant $variant,
        Location $location,
        int $change,
        AdjustmentReason $reason,
        ?User $user = null,
        ?string $note = null,
    ): StockMovement {
        if ($change === 0) {
            throw StockException::noChange();
        }

        $this->guard($variant, $location, abs($change));

        return DB::transaction(function () use ($variant, $location, $change, $reason, $user, $note) {
            $locked = $this->lockVariant($variant);
            $level = $this->lockLevel($locked, $location);

            if ($level->quantity + $change < 0) {
                throw StockException::insufficient($locked, $location, $level->quantity, -$change);
            }

            $level->quantity += $change;
            $level->save();

            return $this->record($locked, $location, MovementType::Adjustment, $change, $level->quantity, [
                'user_id' => $user?->id,
                'unit_cost' => $locked->average_cost,
                'reason' => $reason,
                'note' => $note,
            ]);
        });
    }

    public function sell(
        ProductVariant $variant,
        Location $location,
        int $quantity,
        ?int $unitPriceCents = null,
        ?User $user = null,
        ?string $note = null,
    ): StockMovement {
        $this->guard($variant, $location, $quantity);

        return DB::transaction(function () use ($variant, $location, $quantity, $unitPriceCents, $user, $note) {
            $locked = $this->lockVariant($variant);
            $level = $this->lockLevel($locked, $location);

            if ($level->quantity < $quantity) {
                throw StockException::insufficient($locked, $location, $level->quantity, $quantity);
            }

            $level->quantity -= $quantity;
            $level->save();

            return $this->record($locked, $location, MovementType::Sale, -$quantity, $level->quantity, [
                'user_id' => $user?->id,
                'unit_cost' => $locked->average_cost,
                'unit_price_cents' => $unitPriceCents ?? $locked->price_cents,
                'note' => $note,
            ]);
        });
    }

    private function guard(ProductVariant $variant, Location $location, int $quantity): void
    {
        if ($quantity <= 0) {
            throw StockException::quantityMustBePositive();
        }

    }

    private function lockVariant(ProductVariant $variant): ProductVariant
    {
        return ProductVariant::withoutGlobalScopes()->lockForUpdate()->findOrFail($variant->id);
    }

    private function lockLevel(ProductVariant $variant, Location $location): StockLevel
    {
        $level = StockLevel::query()
            ->where('product_variant_id', $variant->id)
            ->where('location_id', $location->id)
            ->lockForUpdate()
            ->first();

        return $level ?? StockLevel::create([
            'product_variant_id' => $variant->id,
            'location_id' => $location->id,
            'quantity' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function record(
        ProductVariant $variant,
        Location $location,
        MovementType $type,
        int $quantity,
        int $balanceAfter,
        array $attributes,
    ): StockMovement {
        return StockMovement::create([
            'shop_id' => $variant->shop_id,
            'product_variant_id' => $variant->id,
            'location_id' => $location->id,
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $balanceAfter,
            ...$attributes,
        ]);
    }
}
