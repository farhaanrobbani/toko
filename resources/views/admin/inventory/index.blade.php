<x-admin-layout title="Inventori">
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Nilai Stok {{ $warehouse->name }}</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ rupiah($stockValue) }}</p>
            <p class="mt-1 text-xs text-gray-500">Berdasarkan HPP moving average.</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Stok Menipis</p>
            <p class="mt-2 text-2xl font-semibold {{ $lowStockCount > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ number_format($lowStockCount, 0, ',', '.') }} SKU</p>
            <a href="{{ route('admin.inventory.index', ['warehouse' => $warehouse->id, 'low' => 1]) }}" class="mt-1 inline-block text-xs font-medium text-indigo-600 hover:text-indigo-700">Lihat yang menipis</a>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Total SKU</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($products->total(), 0, ',', '.') }}</p>
            <a href="{{ route('admin.adjustments.create', ['warehouse' => $warehouse->id]) }}" class="mt-1 inline-block text-xs font-medium text-indigo-600 hover:text-indigo-700">Catat penyesuaian stok</a>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div>
                <h2 class="font-semibold text-gray-900">Stok per Lokasi</h2>
                <p class="text-sm text-gray-500">Pilih lokasi untuk melihat ketersediaan dan nilai stok.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.purchases.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Barang Masuk</a>
                <a href="{{ route('admin.transfers.create') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Transfer</a>
                <a href="{{ route('admin.stock-counts.create') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Opname</a>
            </div>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-gray-200 bg-gray-50 px-5 py-3">
            <div>
                <x-input-label for="warehouse" value="Lokasi" class="text-xs" />
                <select id="warehouse" name="warehouse" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" onchange="this.form.submit()">
                    @foreach ($warehouses as $option)
                        <option value="{{ $option->id }}" @selected($option->is($warehouse))>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-48 flex-1">
                <x-input-label for="q" value="Cari" class="text-xs" />
                <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="request('q')" placeholder="Nama, SKU, atau barcode" />
            </div>
            <label class="flex items-center gap-2 pb-2 text-sm text-gray-700">
                <input type="checkbox" name="low" value="1" @checked(request()->boolean('low')) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                Hanya stok menipis
            </label>
            <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Filter</button>
            @if (request()->filled('q') || request()->boolean('low'))
                <a href="{{ route('admin.inventory.index', ['warehouse' => $warehouse->id]) }}" class="px-2 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Produk</th>
                        <th class="px-5 py-3">Kategori</th>
                        <th class="px-5 py-3 text-right">Stok</th>
                        <th class="px-5 py-3 text-right">Reservasi</th>
                        <th class="px-5 py-3 text-right">Min.</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($products as $product)
                        @php
                            $stockQty = (float) $product->stock_qty;
                            $isLow = $stockQty <= (float) $product->min_stock;
                        @endphp
                        <tr class="{{ $isLow ? 'bg-amber-50/40' : '' }}">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">{{ $product->name }}</p>
                                <p class="text-xs text-gray-500">{{ $product->sku }} · {{ $product->unit }}</p>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ $product->category?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ qty($stockQty) }}</td>
                            <td class="px-5 py-3 text-right text-gray-500">{{ qty($product->reserved_qty) }}</td>
                            <td class="px-5 py-3 text-right text-gray-500">{{ qty($product->min_stock) }}</td>
                            <td class="px-5 py-3">
                                @if ($isLow)
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Menipis</span>
                                @else
                                    <span class="inline-flex rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">Aman</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.inventory.show', $product) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Kartu Stok</a>
                                    <a href="{{ route('admin.adjustments.create', ['warehouse' => $warehouse->id, 'product' => $product->id]) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Sesuaikan</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-gray-500">Tidak ada produk yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
