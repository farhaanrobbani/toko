<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'productCount' => Product::query()->count(),
            'activeProductCount' => Product::query()->active()->count(),
            'categoryCount' => Category::query()->count(),
            'warehouseCount' => Warehouse::query()->active()->count(),
            'recentProducts' => Product::query()->latest()->limit(5)->get(),
        ]);
    }
}
