<?php

use App\Http\Controllers\ProductExportController;
use App\Http\Controllers\ProductTemplateController;
use App\Livewire\Categories;
use App\Livewire\Dashboard;
use App\Livewire\Locations;
use App\Livewire\Movements;
use App\Livewire\Products;
use App\Livewire\PurchaseOrders;
use App\Livewire\Reports;
use App\Livewire\Stock;
use App\Livewire\Suppliers;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('welcome'))->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::get('products', Products\Index::class)->name('products.index');
    Route::get('products/create', Products\Form::class)->name('products.create');
    Route::get('products/import', Products\Import::class)->name('products.import');
    Route::get('products/export', ProductExportController::class)->name('products.export');
    Route::get('products/template', ProductTemplateController::class)->name('products.template');
    Route::get('products/{product}', Products\Show::class)->name('products.show');
    Route::get('products/{product}/edit', Products\Form::class)->name('products.edit');

    Route::get('stock/receive', Stock\Receive::class)->name('stock.receive');
    Route::get('stock/sell', Stock\Sell::class)->name('stock.sell');
    Route::get('stock/adjust', Stock\Adjust::class)->name('stock.adjust');
    Route::get('stock/transfer', Stock\Transfer::class)->name('stock.transfer');

    Route::get('movements', Movements\Index::class)->name('movements.index');

    Route::get('purchase-orders', PurchaseOrders\Index::class)->name('purchase-orders.index');
    Route::get('purchase-orders/create', PurchaseOrders\Form::class)->name('purchase-orders.create');
    Route::get('purchase-orders/{purchaseOrder}', PurchaseOrders\Show::class)->name('purchase-orders.show');
    Route::get('purchase-orders/{purchaseOrder}/edit', PurchaseOrders\Form::class)->name('purchase-orders.edit');

    Route::get('reports/low-stock', Reports\LowStock::class)->name('reports.low-stock');

    Route::get('suppliers', Suppliers\Index::class)->name('suppliers.index');
    Route::get('suppliers/create', Suppliers\Form::class)->name('suppliers.create');
    Route::get('suppliers/{supplier}/edit', Suppliers\Form::class)->name('suppliers.edit');

    Route::get('categories', Categories\Index::class)->name('categories.index');
    Route::get('locations', Locations\Index::class)->name('locations.index');

    Route::view('profile', 'profile')->name('profile');

    Route::middleware('can:manage-shop')->group(function () {
        Route::get('reports/valuation', Reports\Valuation::class)->name('reports.valuation');
    });
});

require __DIR__.'/auth.php';
