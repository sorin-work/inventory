<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
