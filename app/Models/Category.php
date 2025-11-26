<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use BelongsToShop, HasFactory;

    protected $fillable = ['shop_id', 'name'];
}
