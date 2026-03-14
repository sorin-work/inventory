<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

test('the demo seeder builds a shop with history', function () {
    $this->seed(DemoSeeder::class);

    $demo = User::where('email', 'demo@example.com')->sole();
    $shop = $demo->shop;

    expect($demo->isOwner())->toBeTrue()
        ->and($shop->users()->count())->toBe(2)
        ->and($shop->locations()->count())->toBe(3)
        ->and($shop->variants()->count())->toBeGreaterThan(40)
        ->and(StockMovement::where('shop_id', $shop->id)->count())->toBeGreaterThan(500);

    $statuses = PurchaseOrder::where('shop_id', $shop->id)->pluck('status')->unique();
    expect($statuses)->toContain(PurchaseOrderStatus::Draft, PurchaseOrderStatus::Ordered, PurchaseOrderStatus::PartiallyReceived, PurchaseOrderStatus::Received);

    // Stock levels always match the ledger.
    $fromLedger = StockMovement::where('shop_id', $shop->id)->sum('quantity');
    expect((int) StockLevel::sum('quantity'))->toBe((int) $fromLedger)
        ->and(StockLevel::where('quantity', '<', 0)->count())->toBe(0);

    $this->actingAs($demo)->get(route('dashboard'))->assertOk()->assertSee('Harbour Lane Goods');
});

test('the database seeder only seeds the demo when asked', function () {
    config(['inventory.seed_demo' => false]);
    $this->seed(DatabaseSeeder::class);
    expect(User::count())->toBe(0);

    config(['inventory.seed_demo' => true]);
    $this->seed(DatabaseSeeder::class);
    expect(User::where('email', 'demo@example.com')->exists())->toBeTrue();
});
