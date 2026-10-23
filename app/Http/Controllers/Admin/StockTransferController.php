<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\StockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    public function index(Request $request): View
    {
        $transfers = StockTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'user'])
            ->withCount('items')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.transfers.index', ['transfers' => $transfers]);
    }

    public function create(): View
    {
        $stockLevels = StockLevel::query()
            ->get()
            ->groupBy('warehouse_id')
            ->map(fn ($levels) => $levels->pluck('qty', 'product_id'));

        return view('admin.transfers.create', [
            'warehouses' => Warehouse::query()->active()->orderBy('name')->get(),
            'products' => Product::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'unit'])
                ->toArray(),
            'stockLevels' => $stockLevels,
        ]);
    }

    public function store(Request $request, StockTransferService $service): RedirectResponse
    {
        $data = $request->validate([
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'different:from_warehouse_id', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $transfer = $service->create(
            fromWarehouseId: (int) $data['from_warehouse_id'],
            toWarehouseId: (int) $data['to_warehouse_id'],
            items: $data['items'],
            note: $data['note'] ?? null,
            user: $request->user(),
        );

        return redirect()->route('admin.transfers.show', $transfer)
            ->with('status', 'Transfer dibuat. Kirim barang untuk mengurangi stok asal.');
    }

    public function show(StockTransfer $transfer): View
    {
        return view('admin.transfers.show', [
            'transfer' => $transfer->load(['items.product', 'fromWarehouse', 'toWarehouse', 'user']),
        ]);
    }

    public function ship(Request $request, StockTransfer $transfer, StockTransferService $service): RedirectResponse
    {
        try {
            $service->ship($transfer, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Barang dikirim. Stok lokasi asal sudah dikurangi.');
    }

    public function receive(Request $request, StockTransfer $transfer, StockTransferService $service): RedirectResponse
    {
        try {
            $service->receive($transfer, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Transfer diterima. Stok lokasi tujuan sudah ditambahkan.');
    }

    public function cancel(StockTransfer $transfer, StockTransferService $service): RedirectResponse
    {
        try {
            $service->cancel($transfer);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Transfer draft dibatalkan.');
    }
}
