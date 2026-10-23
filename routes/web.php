<?php

use App\Http\Controllers\Admin\AdjustmentController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StockCountController;
use App\Http\Controllers\Admin\StockTransferController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/dashboard', function () {
    return auth()->user()->isOwner()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('pos.index');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
});

Route::middleware(['auth', 'role:owner'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('products', ProductController::class)->except('show');
    Route::resource('warehouses', WarehouseController::class)->except('show');

    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/{product}', [InventoryController::class, 'show'])->name('inventory.show');

    Route::get('adjustments/create', [AdjustmentController::class, 'create'])->name('adjustments.create');
    Route::post('adjustments', [AdjustmentController::class, 'store'])->name('adjustments.store');

    Route::resource('suppliers', SupplierController::class)->except('show');
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);

    Route::resource('transfers', StockTransferController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('transfers/{transfer}/ship', [StockTransferController::class, 'ship'])->name('transfers.ship');
    Route::post('transfers/{transfer}/receive', [StockTransferController::class, 'receive'])->name('transfers.receive');
    Route::post('transfers/{transfer}/cancel', [StockTransferController::class, 'cancel'])->name('transfers.cancel');

    Route::resource('stock-counts', StockCountController::class)
        ->only(['index', 'create', 'store', 'show', 'update'])
        ->parameters(['stock-counts' => 'stockCount']);
    Route::post('stock-counts/{stockCount}/complete', [StockCountController::class, 'complete'])->name('stock-counts.complete');
    Route::post('stock-counts/{stockCount}/cancel', [StockCountController::class, 'cancel'])->name('stock-counts.cancel');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
});

require __DIR__.'/auth.php';
