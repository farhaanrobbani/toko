<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        return view('admin.warehouses.index', [
            'warehouses' => Warehouse::query()->orderByDesc('is_default')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.warehouses.create', [
            'isDefault' => ! Warehouse::query()->where('is_default', true)->exists(),
        ]);
    }

    public function store(WarehouseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active');

        if (! $data['is_default'] && ! Warehouse::query()->where('is_default', true)->exists()) {
            $data['is_default'] = true;
        }

        Warehouse::query()->create($data);

        return redirect()->route('admin.warehouses.index')->with('status', 'Lokasi berhasil ditambahkan.');
    }

    public function edit(Warehouse $warehouse): View
    {
        return view('admin.warehouses.edit', [
            'warehouse' => $warehouse,
        ]);
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $request->validated();
        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active');

        $warehouse->update($data);

        return redirect()->route('admin.warehouses.index')->with('status', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->is_default) {
            return back()->withErrors(['warehouse' => 'Lokasi default tidak bisa dihapus.']);
        }

        $warehouse->delete();

        return redirect()->route('admin.warehouses.index')->with('status', 'Lokasi berhasil dihapus.');
    }
}
