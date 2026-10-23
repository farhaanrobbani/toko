<x-admin-layout :title="'Stok '.$product->name">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.inventory.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">&larr; Kembali ke inventori</a>
            <h2 class="mt-2 text-2xl font-semibold text-gray-900">{{ $product->name }}</h2>
            <p class="text-sm text-gray-500">{{ $product->sku }} · {{ $product->category?->name ?? 'Tanpa kategori' }} · {{ $product->unit }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.products.edit', $product) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Edit Produk</a>
            <a href="{{ route('admin.adjustments.create', ['product' => $product->id]) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Penyesuaian</a>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($warehouses as $warehouse)
            @php($level = $levels->get($warehouse->id))
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-sm text-gray-500">{{ $warehouse->name }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ qty($level?->qty ?? 0) }}</p>
                <p class="text-xs text-gray-500">Reservasi {{ qty($level?->reserved_qty ?? 0) }}</p>
            </div>
        @endforeach
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-gray-500">HPP Saat Ini</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ rupiah($product->cost_price) }}</p>
            <p class="text-xs text-gray-500">Harga jual {{ rupiah($product->sell_price) }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h3 class="font-semibold text-gray-900">Kartu Stok</h3>
            <p class="text-sm text-gray-500">Semua pergerakan stok produk ini, terbaru di atas.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-5 py-3">Tipe</th>
                        <th class="px-5 py-3">Lokasi</th>
                        <th class="px-5 py-3">Referensi</th>
                        <th class="px-5 py-3 text-right">Jumlah</th>
                        <th class="px-5 py-3 text-right">HPP</th>
                        <th class="px-5 py-3">Oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($movements as $movement)
                        <tr>
                            <td class="px-5 py-3 text-gray-700">{{ $movement->occurred_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">{{ $movement->type->label() }}</span>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ $movement->warehouse?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-xs text-gray-500">
                                @if ($movement->reference_type)
                                    {{ class_basename($movement->reference_type) }} #{{ $movement->reference_id }}
                                @else
                                    —
                                @endif
                                @if ($movement->note)
                                    <p class="mt-0.5 text-gray-400">{{ $movement->note }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right font-semibold {{ (float) $movement->quantity < 0 ? 'text-red-600' : 'text-green-700' }}">
                                {{ (float) $movement->quantity > 0 ? '+' : '' }}{{ qty($movement->quantity) }}
                            </td>
                            <td class="px-5 py-3 text-right text-gray-500">{{ $movement->unit_cost !== null ? rupiah($movement->unit_cost) : '—' }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $movement->user?->name ?? 'Sistem' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-gray-500">Belum ada pergerakan stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($movements->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
