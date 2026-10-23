<x-admin-layout title="Pesanan Online">
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div>
                <h2 class="font-semibold text-gray-900">Pesanan Online</h2>
                <p class="text-sm text-gray-500">Konfirmasi pembayaran, proses, dan kirim pesanan pelanggan.</p>
            </div>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-gray-200 bg-gray-50 px-5 py-3">
            <div class="min-w-48 flex-1">
                <x-input-label for="q" value="Cari" class="text-xs" />
                <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="request('q')" placeholder="No. pesanan, nama, atau telepon" />
            </div>
            <div>
                <x-input-label for="status" value="Status" class="text-xs" />
                <select id="status" name="status" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua status</option>
                    @foreach (\App\Enums\OrderStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Filter</button>
            @if (request()->filled('q') || request()->filled('status'))
                <a href="{{ route('admin.orders.index') }}" class="px-2 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Pesanan</th>
                        <th class="px-5 py-3">Pelanggan</th>
                        <th class="px-5 py-3">Pembayaran</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        <tr class="{{ $order->isPendingPayment() ? 'bg-amber-50/30' : '' }}">
                            <td class="px-5 py-3">
                                <p class="font-mono text-xs font-medium text-gray-900">{{ $order->order_number }}</p>
                                <p class="text-xs text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->items_count }} item</p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">{{ $order->customer_name }}</p>
                                <p class="text-xs text-gray-500">{{ $order->customer_phone }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-gray-700">{{ $order->payment_method->label() }}</p>
                                @if ($order->payment_proof_path)
                                    <p class="text-xs text-green-600">Bukti terunggah</p>
                                @else
                                    <p class="text-xs text-gray-400">Belum ada bukti</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ rupiah($order->total) }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.orders.show', $order) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">Belum ada pesanan online.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
