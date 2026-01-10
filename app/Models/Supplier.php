<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use BelongsToShop, HasFactory;

    protected $fillable = [
        'shop_id',
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'lead_time_days',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'lead_time_days' => 'integer',
        ];
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
