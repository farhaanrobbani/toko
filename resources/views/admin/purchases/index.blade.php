<x-admin-layout title="Pembelian">
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div>
                <h2 class="font-semibold text-gray-900">Riwayat Pembelian</h2>
                <p class="text-sm text-gray-500">Pembelian menambah stok dan memperbarui HPP moving average.</p>
            </div>
            <a href="{{ route('admin.purchases.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Pembelian Baru</a>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-gray-200 bg-gray-50 px-5 py-3">
            <div class="min-w-48 flex-1">
                <x-input-label for="q" value="Cari" class="text-xs" />
                <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="request('q')" placeholder="No. invoice atau supplier" />
            </div>
            <div>
                <x-input-label for="warehouse" value="Lokasi" class="text-xs" />
                <select id="warehouse" name="warehouse" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua lokasi</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((string) request('warehouse') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Filter</button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Supplier / Invoice</th>
                        <th class="px-5 py-3">Lokasi</th>
                        <th class="px-5 py-3 text-right">Item</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($purchases as $purchase)
                        <tr>
                            <td class="px-5 py-3 text-gray-700">{{ $purchase->purchased_at->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">{{ $purchase->supplier?->name ?? 'Tanpa supplier' }}</p>
                                <p class="text-xs text-gray-500">{{ $purchase->invoice_number ?: 'Tanpa invoice' }}</p>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ $purchase->warehouse?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ number_format($purchase->items_count, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ rupiah($purchase->total) }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.purchases.show', $purchase) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">Belum ada pembelian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($purchases->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
