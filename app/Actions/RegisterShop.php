<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\Location;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Signing up creates the shop, its owner and a first stock location so the
 * new owner can start adding products straight away.
 */
class RegisterShop
{
    /**
     * @param  array{shop_name: string, name: string, email: string, password: string, currency?: string}  $data
     */
    public function __invoke(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $shop = Shop::create([
                'name' => $data['shop_name'],
                'currency' => $data['currency'] ?? 'USD',
            ]);

            Location::create([
                'shop_id' => $shop->id,
                'name' => 'Main store',
                'code' => 'MAIN',
                'is_default' => true,
            ]);

            return User::create([
                'shop_id' => $shop->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => Role::Owner,
            ]);
        });
    }
}
