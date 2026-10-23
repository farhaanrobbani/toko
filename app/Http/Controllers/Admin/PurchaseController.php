<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $purchases = Purchase::query()
            ->with(['supplier', 'warehouse', 'user'])
            ->withCount('items')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';

                $query->where(fn ($query) => $query
                    ->where('invoice_number', 'like', $term)
                    ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', $term)));
            })
            ->when($request->filled('warehouse'), fn ($query) => $query->where('warehouse_id', $request->integer('warehouse')))
            ->latest('purchased_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.purchases.index', [
            'purchases' => $purchases,
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.purchases.create', [
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->active()->orderBy('name')->get(),
            'products' => Product::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'unit', 'cost_price'])
                ->toArray(),
        ]);
    }

    public function store(Request $request, PurchaseService $service): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'purchased_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $purchase = $service->create($data, $request->user());

        return redirect()->route('admin.purchases.show', $purchase)
            ->with('status', 'Pembelian berhasil dicatat dan stok sudah diperbarui.');
    }

    public function show(Purchase $purchase): View
    {
        return view('admin.purchases.show', [
            'purchase' => $purchase->load(['supplier', 'warehouse', 'user', 'items.product']),
        ]);
    }
}
