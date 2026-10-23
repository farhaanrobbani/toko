<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockCount;
use App\Models\Warehouse;
use App\Services\StockCountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockCountController extends Controller
{
    public function index(Request $request): View
    {
        $counts = StockCount::query()
            ->with(['warehouse', 'user'])
            ->withCount('items')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.stock-counts.index', ['counts' => $counts]);
    }

    public function create(): View
    {
        return view('admin.stock-counts.create', [
            'warehouses' => Warehouse::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, StockCountService $service): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $count = $service->create(
            warehouse: Warehouse::query()->findOrFail($data['warehouse_id']),
            note: $data['note'] ?? null,
            user: $request->user(),
        );

        return redirect()->route('admin.stock-counts.show', $count)
            ->with('status', 'Sesi opname dibuat. Masukkan jumlah fisik lalu simpan.');
    }

    public function show(StockCount $stockCount): View
    {
        return view('admin.stock-counts.show', [
            'count' => $stockCount->load(['items.product', 'warehouse', 'user']),
        ]);
    }

    public function update(Request $request, StockCount $stockCount, StockCountService $service): RedirectResponse
    {
        $data = $request->validate([
            'counted' => ['array'],
            'counted.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $service->saveCounts($stockCount, $data['counted'] ?? []);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Jumlah fisik disimpan. Periksa selisih sebelum menyelesaikan opname.');
    }

    public function complete(Request $request, StockCount $stockCount, StockCountService $service): RedirectResponse
    {
        try {
            $service->complete($stockCount, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Opname selesai. Selisih stok sudah disesuaikan.');
    }

    public function cancel(StockCount $stockCount, StockCountService $service): RedirectResponse
    {
        try {
            $service->cancel($stockCount);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Opname draft dibatalkan.');
    }
}
