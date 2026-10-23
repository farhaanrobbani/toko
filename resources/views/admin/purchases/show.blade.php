<x-admin-layout :title="'Pembelian #'.$purchase->id">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.purchases.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">&larr; Kembali ke pembelian</a>
            <h2 class="mt-2 text-2xl font-semibold text-gray-900">Pembelian #{{ $purchase->id }}</h2>
            <p class="text-sm text-gray-500">{{ $purchase->purchased_at->format('d/m/Y') }} · {{ $purchase->warehouse?->name }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-5 py-3 text-right shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Total</p>
            <p class="text-xl font-semibold text-gray-900">{{ rupiah($purchase->total) }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Supplier</p>
            <p class="mt-1 font-medium text-gray-900">{{ $purchase->supplier?->name ?? 'Tanpa supplier' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">No. Invoice</p>
            <p class="mt-1 font-medium text-gray-900">{{ $purchase->invoice_number ?: '—' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Dicatat oleh</p>
            <p class="mt-1 font-medium text-gray-900">{{ $purchase->user?->name ?? 'Sistem' }}</p>
        </div>
    </div>

    @if ($purchase->note)
        <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Catatan</p>
            <p class="mt-1">{{ $purchase->note }}</p>
        </div>
    @endif

    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h3 class="font-semibold text-gray-900">Rincian Item</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Produk</th>
                        <th class="px-5 py-3 text-right">Jumlah</th>
                        <th class="px-5 py-3 text-right">Harga Beli</th>
                        <th class="px-5 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($purchase->items as $item)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                                <p class="text-xs text-gray-500">{{ $item->sku }}</p>
                            </td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ qty($item->quantity) }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ rupiah($item->unit_cost) }}</td>
                            <td class="px-5 py-3 text-right font-medium text-gray-900">{{ rupiah($item->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right font-semibold text-gray-700">Total</td>
                        <td class="px-5 py-3 text-right text-base font-semibold text-gray-900">{{ rupiah($purchase->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-admin-layout>
