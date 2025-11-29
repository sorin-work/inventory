<?php

use App\Livewire\Categories;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware('auth')->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('categories', Categories\Index::class)->name('categories.index');

    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
