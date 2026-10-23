<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->withCount('purchases')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';

                $query->where(fn ($query) => $query
                    ->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.suppliers.index', ['suppliers' => $suppliers]);
    }

    public function create(): View
    {
        return view('admin.suppliers.create', ['supplier' => new Supplier]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::query()->create($this->validated($request));

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.edit', ['supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists()) {
            return back()->withErrors(['supplier' => 'Supplier masih memiliki riwayat pembelian dan tidak bisa dihapus.']);
        }

        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier berhasil dihapus.');
    }

    /**
     * @return array<string, string|null>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
