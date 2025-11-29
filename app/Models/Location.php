<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function makeDefault(): void
    {
        static::query()
            ->where('shop_id', $this->shop_id)
            ->whereKeyNot($this->getKey())
            ->update(['is_default' => false]);

        $this->forceFill(['is_default' => true])->save();
    }
}
