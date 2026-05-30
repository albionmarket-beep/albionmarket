<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\BuyOrders;
use App\Livewire\SalesOrders;
use App\Livewire\Inventory;
use App\Livewire\Reports;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('buy-order',   BuyOrders::class)->name('buy-order');
    Route::get('sales-order', SalesOrders::class)->name('sales-order');
    Route::get('inventory',   Inventory::class)->name('inventory');
    Route::get('reports',     Reports::class)->name('reports');
});

require __DIR__.'/settings.php';