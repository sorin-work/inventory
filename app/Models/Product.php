<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToShop, HasFactory;

    public const UNITS = ['each', 'pack', 'box', 'pair', 'kg', 'g', 'l', 'ml', 'm'];

    protected $fillable = [
        'shop_id',
        'category_id',
        'supplier_id',
        'name',
        'description',
        'unit',
        'option_names',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'option_names' => 'array',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function hasOptions(): bool
    {
        return ! empty($this->option_names);
    }
}
