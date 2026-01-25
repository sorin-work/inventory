<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\BelongsToShop;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use BelongsToShop, HasFactory;

    protected $fillable = [
        'shop_id',
        'supplier_id',
        'location_id',
        'user_id',
        'number',
        'status',
        'ordered_at',
        'expected_on',
        'received_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'ordered_at' => 'datetime',
            'expected_on' => 'date',
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
     * @return HasMany<PurchaseOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('id');
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @param  Builder<PurchaseOrder>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', PurchaseOrderStatus::open());
    }

    public function totalCents(): int
    {
        return (int) $this->lines->sum(fn (PurchaseOrderLine $line) => $line->totalCents());
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen()
            && $this->expected_on !== null
            && $this->expected_on->isBefore(today());
    }

    public function hasReceipts(): bool
    {
        return $this->lines->contains(fn (PurchaseOrderLine $line) => $line->quantity_received > 0);
    }
}
