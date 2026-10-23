<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(Request $request, CartService $cartService): View
    {
        $warehouse = $cartService->warehouse();

        $products = Product::query()
            ->active()
            ->with('category')
            ->withSum(['stockLevels as stock_qty' => fn ($query) => $query->where('warehouse_id', $warehouse?->getKey())], 'qty')
            ->withSum(['stockLevels as reserved_qty' => fn ($query) => $query->where('warehouse_id', $warehouse?->getKey())], 'reserved_qty')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';

                $query->where(fn ($query) => $query
                    ->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term));
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('store.index', [
            'products' => $products,
            'categories' => Category::query()
                ->withCount(['products' => fn ($query) => $query->active()])
                ->orderBy('name')
                ->get(),
            'warehouse' => $warehouse,
        ]);
    }

    public function show(Product $product, CartService $cartService): View
    {
        abort_unless($product->is_active, 404);

        $warehouse = $cartService->warehouse();

        $related = Product::query()
            ->active()
            ->with('category')
            ->withSum(['stockLevels as stock_qty' => fn ($query) => $query->where('warehouse_id', $warehouse?->getKey())], 'qty')
            ->withSum(['stockLevels as reserved_qty' => fn ($query) => $query->where('warehouse_id', $warehouse?->getKey())], 'reserved_qty')
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->getKey())
            ->limit(4)
            ->get();

        return view('store.show', [
            'product' => $product->load('category'),
            'available' => $cartService->availableFor($product),
            'related' => $related,
            'warehouse' => $warehouse,
        ]);
    }
}
