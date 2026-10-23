@php
    use App\Enums\OrderStatus;
    $customerWa = wa_number($order->customer_phone);
@endphp

<x-admin-layout :title="'Pesanan '.$order->order_number">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.orders.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">&larr; Kembali ke pesanan</a>
            <h2 class="mt-2 text-2xl font-semibold text-gray-900">{{ $order->order_number }}</h2>
            <p class="text-sm text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->payment_method->label() }} · {{ $order->delivery_method->label() }}</p>
        </div>
        <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        @if ($order->status === OrderStatus::PendingPayment)
            <form method="POST" action="{{ route('admin.orders.confirm', $order) }}" onsubmit="return confirm('Konfirmasi pembayaran dan kurangi stok?')">
                @csrf
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Konfirmasi Pembayaran</button>
            </form>
            <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Batalkan pesanan dan lepas reservasi stok?')">
                @csrf
                <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Batalkan</button>
            </form>
        @endif

        @if ($order->status->canAdvanceTo(OrderStatus::Processing))
            <form method="POST" action="{{ route('admin.orders.advance', $order) }}">
                @csrf
                <input type="hidden" name="status" value="{{ OrderStatus::Processing->value }}">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Proses Pesanan</button>
            </form>
        @endif

        @if ($order->status->canAdvanceTo(OrderStatus::Shipped))
            <form method="POST" action="{{ route('admin.orders.advance', $order) }}">
                @csrf
                <input type="hidden" name="status" value="{{ OrderStatus::Shipped->value }}">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tandai Dikirim</button>
            </form>
        @endif

        @if ($order->status->canAdvanceTo(OrderStatus::Completed))
            <form method="POST" action="{{ route('admin.orders.advance', $order) }}">
                @csrf
                <input type="hidden" name="status" value="{{ OrderStatus::Completed->value }}">
                <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">Selesaikan Pesanan</button>
            </form>
        @endif

        @if ($customerWa)
            <a href="https://wa.me/{{ $customerWa }}" target="_blank" rel="noopener" class="rounded-lg border border-green-200 bg-white px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50">WhatsApp Pelanggan</a>
        @endif
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">Item Pesanan</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Produk</th>
                                <th class="px-5 py-3 text-right">Harga</th>
                                <th class="px-5 py-3 text-right">Jumlah</th>
                                <th class="px-5 py-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $item->sku }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-right text-gray-700">{{ rupiah($item->price) }}</td>
                                    <td class="px-5 py-3 text-right text-gray-700">{{ qty($item->quantity) }} {{ $item->unit }}</td>
                                    <td class="px-5 py-3 text-right font-medium text-gray-900">{{ rupiah($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-5 py-2 text-right text-sm text-gray-600">Subtotal</td>
                                <td class="px-5 py-2 text-right text-sm text-gray-900">{{ rupiah($order->subtotal) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-5 py-2 text-right text-sm text-gray-600">Ongkir</td>
                                <td class="px-5 py-2 text-right text-sm text-gray-900">{{ rupiah($order->shipping_cost) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-5 py-3 text-right font-semibold text-gray-900">Total</td>
                                <td class="px-5 py-3 text-right text-base font-bold text-gray-900">{{ rupiah($order->total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-gray-900">Bukti Pembayaran</h3>
                @if ($order->payment_proof_path)
                    <div class="mt-3 flex flex-wrap items-center gap-4">
                        <a href="{{ asset('storage/'.$order->payment_proof_path) }}" target="_blank" rel="noopener">
                            <img src="{{ asset('storage/'.$order->payment_proof_path) }}" alt="Bukti pembayaran" class="h-40 rounded-lg border border-gray-200 object-cover">
                        </a>
                        <p class="text-sm text-gray-600">Diupload {{ $order->payment_uploaded_at?->format('d/m/Y H:i') }}.</p>
                    </div>
                @else
                    <p class="mt-2 text-sm text-gray-500">Belum ada bukti pembayaran yang diunggah.</p>
                @endif
            </div>
        </div>

        <aside class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-gray-900">Pelanggan</h3>
                <div class="mt-3 space-y-2 text-sm text-gray-600">
                    <p class="font-medium text-gray-900">{{ $order->customer_name }}</p>
                    <p>{{ $order->customer_phone }}</p>
                    @if ($order->customer_address)
                        <p class="whitespace-pre-line">{{ $order->customer_address }}</p>
                    @else
                        <p class="text-gray-400">Ambil di toko.</p>
                    @endif
                    @if ($order->customer_note)
                        <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs">Catatan: {{ $order->customer_note }}</p>
                    @endif
                </div>
            </div>

            @if ($order->status === OrderStatus::PendingPayment)
                <div class="rounded-xl border border-gray-200 bg-white p-5 text-sm shadow-sm">
                    <h3 class="font-semibold text-gray-900">Ongkos Kirim</h3>
                    <form method="POST" action="{{ route('admin.orders.shipping', $order) }}" class="mt-3 flex items-end gap-2">
                        @csrf
                        <div class="flex-1">
                            <x-input-label for="shipping_cost" value="Ongkir (Rp)" class="text-xs" />
                            <input id="shipping_cost" name="shipping_cost" type="number" min="0" step="100" value="{{ old('shipping_cost', (float) $order->shipping_cost) }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">Simpan</button>
                    </form>
                    <p class="mt-2 text-xs text-gray-500">Total akan dihitung ulang: subtotal + ongkir.</p>
                </div>
            @endif

            <div class="rounded-xl border border-gray-200 bg-white p-5 text-sm shadow-sm">
                <h3 class="font-semibold text-gray-900">Info Pesanan</h3>
                <dl class="mt-3 space-y-2 text-gray-600">
                    <div class="flex justify-between gap-3">
                        <dt>Lokasi stok</dt>
                        <dd class="font-medium text-gray-900">{{ $order->warehouse?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt>Kedaluwarsa</dt>
                        <dd class="font-medium text-gray-900">{{ $order->expires_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt>Dikonfirmasi</dt>
                        <dd class="font-medium text-gray-900">{{ $order->confirmedBy?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt>Waktu konfirmasi</dt>
                        <dd class="font-medium text-gray-900">{{ $order->confirmed_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </aside>
    </div>
</x-admin-layout>
