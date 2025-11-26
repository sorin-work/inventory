<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
