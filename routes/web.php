<?php

use App\Livewire\Categories;
use App\Livewire\Locations;
use App\Livewire\Suppliers;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware('auth')->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('suppliers', Suppliers\Index::class)->name('suppliers.index');
    Route::get('suppliers/create', Suppliers\Form::class)->name('suppliers.create');
    Route::get('suppliers/{supplier}/edit', Suppliers\Form::class)->name('suppliers.edit');

    Route::get('categories', Categories\Index::class)->name('categories.index');
    Route::get('locations', Locations\Index::class)->name('locations.index');

    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
