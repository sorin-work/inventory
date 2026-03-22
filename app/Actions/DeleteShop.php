<?php

namespace App\Actions;

use App\Models\Shop;
use Illuminate\Support\Facades\DB;

/**
 * Removes a shop and everything in it. Done table by table, children
 * first, because purchase orders deliberately restrict deleting the
 * suppliers, locations and variants they point at.
 */
class DeleteShop
{
    public function __invoke(Shop $shop): void
    {
        DB::transaction(function () use ($shop) {
            $variantIds = DB::table('product_variants')->where('shop_id', $shop->id)->pluck('id');
            $orderIds = DB::table('purchase_orders')->where('shop_id', $shop->id)->pluck('id');

            DB::table('stock_movements')->where('shop_id', $shop->id)->delete();
            DB::table('stock_levels')->whereIn('product_variant_id', $variantIds)->delete();
            DB::table('purchase_order_lines')->whereIn('purchase_order_id', $orderIds)->delete();
            DB::table('purchase_orders')->where('shop_id', $shop->id)->delete();
            DB::table('product_variants')->where('shop_id', $shop->id)->delete();
            DB::table('products')->where('shop_id', $shop->id)->delete();

            foreach (['categories', 'suppliers', 'locations', 'users'] as $table) {
                DB::table($table)->where('shop_id', $shop->id)->delete();
            }

            $shop->delete();
        });
    }
}
