<?php

use App\Livewire\Categories;
use App\Livewire\Locations;
use App\Livewire\Products;
use App\Livewire\Stock;
use App\Livewire\Suppliers;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware('auth')->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('products', Products\Index::class)->name('products.index');
    Route::get('products/create', Products\Form::class)->name('products.create');
    Route::get('products/{product}', Products\Show::class)->name('products.show');
    Route::get('products/{product}/edit', Products\Form::class)->name('products.edit');

    Route::get('stock/receive', Stock\Receive::class)->name('stock.receive');
    Route::get('stock/sell', Stock\Sell::class)->name('stock.sell');
    Route::get('stock/adjust', Stock\Adjust::class)->name('stock.adjust');

    Route::get('suppliers', Suppliers\Index::class)->name('suppliers.index');
    Route::get('suppliers/create', Suppliers\Form::class)->name('suppliers.create');
    Route::get('suppliers/{supplier}/edit', Suppliers\Form::class)->name('suppliers.edit');

    Route::get('categories', Categories\Index::class)->name('categories.index');
    Route::get('locations', Locations\Index::class)->name('locations.index');

    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
