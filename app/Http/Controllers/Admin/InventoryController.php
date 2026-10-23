<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $warehouses = Warehouse::query()->active()->orderBy('name')->get();
        $warehouse = $warehouses->firstWhere('id', $request->integer('warehouse'))
            ?? $warehouses->firstWhere('is_default', true)
            ?? $warehouses->first();

        abort_if($warehouse === null, 404, 'Belum ada lokasi stok aktif.');

        $products = Product::query()
            ->with('category')
            ->leftJoin('stock_levels', function ($join) use ($warehouse): void {
                $join->on('stock_levels.product_id', '=', 'products.id')
                    ->where('stock_levels.warehouse_id', '=', $warehouse->getKey());
            })
            ->select('products.*')
            ->selectRaw('coalesce(stock_levels.qty, 0) as stock_qty')
            ->selectRaw('coalesce(stock_levels.reserved_qty, 0) as reserved_qty')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';

                $query->where(fn ($query) => $query
                    ->where('products.name', 'like', $term)
                    ->orWhere('products.sku', 'like', $term)
                    ->orWhere('products.barcode', 'like', $term));
            })
            ->when($request->boolean('low'), fn ($query) => $query
                ->whereRaw('coalesce(stock_levels.qty, 0) <= products.min_stock'))
            ->orderBy('products.name')
            ->paginate(20)
            ->withQueryString();

        $stockValue = (float) StockLevel::query()
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->where('stock_levels.warehouse_id', $warehouse->getKey())
            ->sum(DB::raw('stock_levels.qty * products.cost_price'));

        $lowStockCount = Product::query()
            ->leftJoin('stock_levels', function ($join) use ($warehouse): void {
                $join->on('stock_levels.product_id', '=', 'products.id')
                    ->where('stock_levels.warehouse_id', '=', $warehouse->getKey());
            })
            ->where('products.is_active', true)
            ->whereRaw('coalesce(stock_levels.qty, 0) <= products.min_stock')
            ->count();

        return view('admin.inventory.index', [
            'warehouses' => $warehouses,
            'warehouse' => $warehouse,
            'products' => $products,
            'stockValue' => $stockValue,
            'lowStockCount' => $lowStockCount,
        ]);
    }

    public function show(Product $product): View
    {
        $warehouses = Warehouse::query()->active()->orderBy('name')->get();

        $movements = StockMovement::query()
            ->with(['warehouse', 'user'])
            ->where('product_id', $product->getKey())
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(30);

        $levels = StockLevel::query()
            ->where('product_id', $product->getKey())
            ->get()
            ->keyBy('warehouse_id');

        return view('admin.inventory.show', [
            'product' => $product->load('category'),
            'warehouses' => $warehouses,
            'movements' => $movements,
            'levels' => $levels,
        ]);
    }
}
