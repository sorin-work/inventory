<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Limits queries to the signed-in user's shop. Console commands and the
 * demo seeder run without a user and see everything.
 */
class ShopScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $shopId = Auth::user()?->shop_id;

        if ($shopId !== null) {
            $builder->where($model->qualifyColumn('shop_id'), $shopId);
        }
    }
}
