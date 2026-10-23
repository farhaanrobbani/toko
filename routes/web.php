<?php

use App\Http\Controllers\Admin\AdjustmentController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StockCountController;
use App\Http\Controllers\Admin\StockTransferController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockAvailabilityController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/produk/{product:slug}', [StorefrontController::class, 'show'])->name('store.products.show');
Route::get('/stok', StockAvailabilityController::class)
    ->middleware('throttle:120,1')
    ->name('stock.live');

Route::get('/keranjang', [CartController::class, 'index'])->name('cart.index');
Route::post('/keranjang/{product}', [CartController::class, 'store'])->name('cart.store');
Route::patch('/keranjang/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/keranjang/{product}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:20,1')->name('checkout.store');

Route::get('/lacak', [OrderTrackingController::class, 'form'])->name('track.form');
Route::post('/lacak', [OrderTrackingController::class, 'lookup'])->name('track.lookup');
Route::get('/pesanan/{order:tracking_token}', [OrderTrackingController::class, 'show'])->name('orders.show');
Route::post('/pesanan/{order:tracking_token}/bukti', [OrderTrackingController::class, 'uploadProof'])
    ->middleware('throttle:10,1')
    ->name('orders.proof');

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
    Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
    Route::get('/pos/sales/{sale}', [PosController::class, 'receipt'])->name('pos.receipt');
});

Route::middleware(['auth', 'role:owner'])->group(function () {
    Route::post('/pos/sales/{sale}/void', [PosController::class, 'void'])->name('pos.void');
});

Route::middleware(['auth', 'role:owner'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('products', ProductController::class)->except('show');
    Route::resource('warehouses', WarehouseController::class)->except('show');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('orders/{order}/shipping', [OrderController::class, 'shipping'])->name('orders.shipping');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/advance', [OrderController::class, 'advance'])->name('orders.advance');

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
