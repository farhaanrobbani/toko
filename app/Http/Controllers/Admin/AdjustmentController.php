<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdjustmentController extends Controller
{
    public function create(): View
    {
        return view('admin.adjustments.create', [
            'warehouses' => Warehouse::query()->active()->orderBy('name')->get(),
            'products' => Product::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'unit', 'cost_price']),
        ]);
    }

    public function store(Request $request, StockService $stockService): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'direction' => ['required', 'in:in,out'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $product = Product::query()->findOrFail($data['product_id']);
        $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);
        $quantity = round((float) $data['quantity'], 3);

        $stockService->record(
            product: $product,
            warehouse: $warehouse,
            type: StockMovementType::Adjustment,
            quantity: $data['direction'] === 'in' ? $quantity : -1 * $quantity,
            unitCost: (float) $product->cost_price,
            note: $data['note'],
            user: $request->user(),
        );

        return redirect()->route('admin.inventory.index', ['warehouse' => $warehouse->getKey()])
            ->with('status', 'Penyesuaian stok berhasil dicatat.');
    }
}
