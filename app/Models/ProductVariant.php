<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Support\AverageCost;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int|null $on_hand loaded with withOnHand()
 */
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use BelongsToShop, HasFactory;

    protected $fillable = [
        'shop_id',
        'product_id',
        'sku',
        'barcode',
        'option_values',
        'price_cents',
        'average_cost',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'option_values' => 'array',
            'price_cents' => 'integer',
            'average_cost' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<StockLevel, $this>
     */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @param  Builder<ProductVariant>  $query
     */
    public function scopeWithOnHand(Builder $query): void
    {
        $query->withSum('stockLevels as on_hand', 'quantity');
    }

    /**
     * @param  Builder<ProductVariant>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $q) use ($term) {
            $like = '%'.$term.'%';

            $q->where('sku', 'like', $like)
                ->orWhere('barcode', $term)
                ->orWhereHas('product', fn (Builder $p) => $p->where('name', 'like', $like));
        });
    }

    public function optionLabel(): string
    {
        return implode(' / ', array_filter($this->option_values ?? [], fn ($v) => $v !== null && $v !== ''));
    }

    public function displayName(): string
    {
        $options = $this->optionLabel();

        return $options === '' ? $this->product->name : $this->product->name.' — '.$options;
    }

    public function onHand(): int
    {
        if (array_key_exists('on_hand', $this->attributes)) {
            return (int) $this->attributes['on_hand'];
        }

        return (int) $this->stockLevels()->sum('quantity');
    }

    public function quantityAt(Location $location): int
    {
        return (int) $this->stockLevels()->where('location_id', $location->id)->value('quantity');
    }

    public function stockValue(): int
    {
        return AverageCost::value($this->onHand(), $this->average_cost);
    }
}
