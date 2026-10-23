<x-admin-layout title="Dashboard">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            ['label' => 'Total Produk', 'value' => number_format($productCount, 0, ',', '.')],
            ['label' => 'Produk Aktif', 'value' => number_format($activeProductCount, 0, ',', '.')],
            ['label' => 'Kategori', 'value' => number_format($categoryCount, 0, ',', '.')],
            ['label' => 'Lokasi Aktif', 'value' => number_format($warehouseCount, 0, ',', '.')],
            ['label' => 'Stok Menipis', 'value' => number_format($lowStockCount, 0, ',', '.').' SKU', 'href' => route('admin.inventory.index', ['low' => 1])],
            ['label' => 'Nilai Stok '.($defaultWarehouse?->name ?? ''), 'value' => rupiah($stockValue), 'href' => route('admin.inventory.index')],
            ['label' => 'Pesanan Menunggu', 'value' => number_format($pendingOrderCount, 0, ',', '.'), 'href' => route('admin.orders.index', ['status' => \App\Enums\OrderStatus::PendingPayment->value])],
        ] as $card)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">{{ $card['label'] }}</p>
                @if (! empty($card['href']))
                    <a href="{{ $card['href'] }}" class="mt-2 block text-2xl font-semibold text-gray-900 hover:text-indigo-600">{{ $card['value'] }}</a>
                @else
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $card['value'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <div class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-gray-900">Produk Terbaru</h2>
            <a href="{{ route('admin.products.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Lihat semua</a>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse ($recentProducts as $product)
                <div class="flex items-center justify-between gap-4 px-5 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900">{{ $product->name }}</p>
                        <p class="text-xs text-gray-500">{{ $product->sku }} · {{ $product->unit }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900">{{ rupiah($product->sell_price) }}</p>
                        <p class="text-xs text-gray-500">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                    </div>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-500">Belum ada produk. Tambahkan produk pertama Anda.</p>
            @endforelse
        </div>
    </div>
</x-admin-layout>
