<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use BelongsToShop, HasFactory;

    protected $fillable = ['shop_id', 'name', 'code', 'is_default'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
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

    public function makeDefault(): void
    {
        static::query()
            ->where('shop_id', $this->shop_id)
            ->whereKeyNot($this->getKey())
            ->update(['is_default' => false]);

        $this->forceFill(['is_default' => true])->save();
    }
}
