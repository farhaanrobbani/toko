<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $warehouses = Warehouse::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $warehouse = $this->resolveWarehouse($request, $warehouses);

        $products = $warehouse
            ? Product::query()
                ->active()
                ->withSum(['stockLevels as stock_qty' => fn ($query) => $query->where('warehouse_id', $warehouse->getKey())], 'qty')
                ->withSum(['stockLevels as reserved_qty' => fn ($query) => $query->where('warehouse_id', $warehouse->getKey())], 'reserved_qty')
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->getKey(),
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'unit' => $product->unit,
                    'price' => (float) $product->sell_price,
                    'stock' => max(0, round((float) $product->stock_qty - (float) $product->reserved_qty, 3)),
                ])
                ->values()
            : collect();

        $oldItems = collect(json_decode((string) old('items', '[]'), true))
            ->filter(fn ($item) => is_array($item) && isset($item['product_id']))
            ->map(fn (array $item) => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (float) ($item['quantity'] ?? 1),
            ])
            ->values()
            ->all();

        return view('pos.index', [
            'warehouses' => $warehouses,
            'warehouse' => $warehouse,
            'products' => $products,
            'oldItems' => $oldItems,
            'recentSales' => Sale::query()
                ->with('cashier')
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    public function store(StoreSaleRequest $request, SaleService $saleService): RedirectResponse
    {
        try {
            $sale = $saleService->checkout($request->validated(), $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->route('pos.receipt', $sale)
            ->with('status', "Transaksi {$sale->sale_number} berhasil disimpan.");
    }

    public function receipt(Sale $sale): View
    {
        return view('pos.receipt', [
            'sale' => $sale->load('items', 'warehouse', 'cashier', 'voidedBy'),
        ]);
    }

    public function void(Request $request, Sale $sale, SaleService $saleService): RedirectResponse
    {
        $data = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $saleService->void($sale, $request->user(), $data['void_reason']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('pos.receipt', $sale)
            ->with('status', "Transaksi {$sale->sale_number} dibatalkan dan stok dikembalikan.");
    }

    private function resolveWarehouse(Request $request, $warehouses): ?Warehouse
    {
        $requestedId = $request->integer('warehouse') ?: (int) old('warehouse_id') ?: (int) Setting::get('pos_warehouse_id');

        $warehouse = $requestedId
            ? $warehouses->firstWhere('id', $requestedId)
            : null;

        return $warehouse
            ?? $warehouses->firstWhere('code', 'toko')
            ?? $warehouses->firstWhere('is_default', true)
            ?? $warehouses->first();
    }
}
