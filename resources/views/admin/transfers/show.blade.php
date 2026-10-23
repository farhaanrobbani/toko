<x-admin-layout :title="'Transfer '.$transfer->reference_number">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.transfers.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">&larr; Kembali ke transfer</a>
            <h2 class="mt-2 text-2xl font-semibold text-gray-900">{{ $transfer->reference_number }}</h2>
            <p class="text-sm text-gray-500">{{ $transfer->fromWarehouse?->name }} &rarr; {{ $transfer->toWarehouse?->name }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($transfer->status === \App\Enums\TransferStatus::Draft)
                <form method="POST" action="{{ route('admin.transfers.ship', $transfer) }}" onsubmit="return confirm('Kirim barang sekarang? Stok asal akan berkurang.')">
                    @csrf
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Kirim Barang</button>
                </form>
                <form method="POST" action="{{ route('admin.transfers.cancel', $transfer) }}" onsubmit="return confirm('Batalkan transfer ini?')">
                    @csrf
                    <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Batalkan</button>
                </form>
            @elseif ($transfer->status === \App\Enums\TransferStatus::InTransit)
                <form method="POST" action="{{ route('admin.transfers.receive', $transfer) }}" onsubmit="return confirm('Terima barang sekarang? Stok tujuan akan bertambah.')">
                    @csrf
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Terima Barang</button>
                </form>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Status</p>
            <p class="mt-1 font-medium text-gray-900">{{ $transfer->status->label() }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Dibuat</p>
            <p class="mt-1 font-medium text-gray-900">{{ $transfer->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Dikirim</p>
            <p class="mt-1 font-medium text-gray-900">{{ $transfer->shipped_at?->format('d/m/Y H:i') ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Diterima</p>
            <p class="mt-1 font-medium text-gray-900">{{ $transfer->received_at?->format('d/m/Y H:i') ?? '—' }}</p>
        </div>
    </div>

    @if ($transfer->note)
        <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Catatan</p>
            <p class="mt-1">{{ $transfer->note }}</p>
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($transfer->items as $item)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                                <p class="text-xs text-gray-500">{{ $item->sku }}</p>
                            </td>
                            <td class="px-5 py-3 text-right font-medium text-gray-900">{{ qty($item->quantity) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
