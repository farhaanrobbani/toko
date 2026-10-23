<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $defaultWarehouse = Warehouse::query()->where('is_default', true)->first()
            ?? Warehouse::query()->active()->first();

        $lowStockCount = 0;
        $stockValue = 0.0;

        if ($defaultWarehouse) {
            $lowStockCount = Product::query()
                ->leftJoin('stock_levels', function ($join) use ($defaultWarehouse): void {
                    $join->on('stock_levels.product_id', '=', 'products.id')
                        ->where('stock_levels.warehouse_id', '=', $defaultWarehouse->getKey());
                })
                ->where('products.is_active', true)
                ->whereRaw('coalesce(stock_levels.qty, 0) <= products.min_stock')
                ->count();

            $stockValue = (float) StockLevel::query()
                ->join('products', 'products.id', '=', 'stock_levels.product_id')
                ->where('stock_levels.warehouse_id', $defaultWarehouse->getKey())
                ->sum(DB::raw('stock_levels.qty * products.cost_price'));
        }

        return view('admin.dashboard', [
            'productCount' => Product::query()->count(),
            'activeProductCount' => Product::query()->active()->count(),
            'categoryCount' => Category::query()->count(),
            'warehouseCount' => Warehouse::query()->active()->count(),
            'lowStockCount' => $lowStockCount,
            'stockValue' => $stockValue,
            'pendingOrderCount' => Order::query()->where('status', OrderStatus::PendingPayment)->count(),
            'defaultWarehouse' => $defaultWarehouse,
            'recentProducts' => Product::query()->latest()->limit(5)->get(),
        ]);
    }
}
