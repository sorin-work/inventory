<?php

namespace App\Models;

use App\Enums\AdjustmentReason;
use App\Enums\MovementType;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line in the stock ledger. Rows are only ever inserted; mistakes are
 * fixed with a correcting adjustment so the history stays honest.
 */
class StockMovement extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'shop_id',
        'product_variant_id',
        'location_id',
        'user_id',
        'type',
        'quantity',
        'balance_after',
        'unit_cost',
        'unit_price_cents',
        'reason',
        'note',
        'transfer_id',
        'purchase_order_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'reason' => AdjustmentReason::class,
            'quantity' => 'integer',
            'balance_after' => 'integer',
            'unit_cost' => 'float',
            'unit_price_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * The other half of a transfer.
     */
    public function counterpart(): ?self
    {
        if ($this->transfer_id === null) {
            return null;
        }

        return static::query()
            ->where('transfer_id', $this->transfer_id)
            ->whereKeyNot($this->getKey())
            ->first();
    }
}
