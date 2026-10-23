@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentMethod;
    use App\Models\Setting;

    $steps = [OrderStatus::PendingPayment, OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Completed];
    $currentIndex = array_search($order->status, $steps, true);
    $adminWa = wa_number(Setting::get('admin_whatsapp') ?: Setting::get('store_phone'));
    $waText = rawurlencode("Halo, saya ingin menanyakan pesanan {$order->order_number} atas nama {$order->customer_name} dengan total ".rupiah($order->total).'.');
@endphp

<x-store-layout :title="'Pesanan '.$order->order_number" :noindex="true">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('home') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">&larr; Kembali belanja</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">Pesanan {{ $order->order_number }}</h1>
            <p class="mt-1 text-sm text-gray-500">Dibuat {{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->delivery_method->label() }}</p>
        </div>
        <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
    </div>

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        @if ($order->status === OrderStatus::Cancelled)
            <p class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700">Pesanan ini telah dibatalkan. Hubungi admin jika Anda sudah melakukan pembayaran.</p>
        @else
            <div class="grid grid-cols-5 gap-2">
                @foreach ($steps as $index => $step)
                    @php
                        $done = $currentIndex !== false && $index <= $currentIndex;
                        $active = $currentIndex !== false && $index === $currentIndex;
                    @endphp
                    <div class="text-center">
                        <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold {{ $done ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-400' }}">
                            {{ $index + 1 }}
                        </div>
                        <p class="mt-2 text-[11px] font-medium leading-tight {{ $active ? 'text-indigo-700' : ($done ? 'text-gray-700' : 'text-gray-400') }}">{{ $step->label() }}</p>
                    </div>
                @endforeach
            </div>
            @if ($order->status === OrderStatus::PendingPayment && $order->expires_at)
                <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">Selesaikan pembayaran sebelum {{ $order->expires_at->format('d/m/Y H:i') }}, atau pesanan dibatalkan otomatis.</p>
            @endif
        @endif
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-gray-900">Rincian Pesanan</h2>
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
                                <td class="px-5 py-2 text-right text-sm text-gray-900">{{ (float) $order->shipping_cost > 0 ? rupiah($order->shipping_cost) : 'Dikonfirmasi admin' }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-5 py-3 text-right font-semibold text-gray-900">Total</td>
                                <td class="px-5 py-3 text-right text-base font-bold text-gray-900">{{ rupiah($order->total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Pembayaran</h2>
                <p class="mt-1 text-sm text-gray-500">Metode: <span class="font-medium text-gray-900">{{ $order->payment_method->label() }}</span></p>

                @if ($order->payment_proof_path)
                    <div class="mt-4 flex flex-wrap items-center gap-4 rounded-xl bg-green-50 p-4">
                        <img src="{{ asset('storage/'.$order->payment_proof_path) }}" alt="Bukti pembayaran" class="h-20 w-20 rounded-lg object-cover ring-1 ring-green-200">
                        <div class="text-sm text-green-800">
                            <p class="font-medium">Bukti pembayaran sudah diunggah.</p>
                            <p class="text-xs">Diupload {{ $order->payment_uploaded_at?->format('d/m/Y H:i') }}. Menunggu verifikasi admin.</p>
                        </div>
                    </div>
                @endif

                @if ($order->status === OrderStatus::PendingPayment)
                    @if ($order->payment_method === PaymentMethod::Qris)
                        @if (Setting::get('qris_image_path'))
                            <div class="mt-4 rounded-xl border border-gray-200 p-4">
                                <p class="text-sm font-medium text-gray-900">Scan QRIS berikut</p>
                                <img src="{{ asset('storage/'.Setting::get('qris_image_path')) }}" alt="QRIS {{ Setting::get('store_name') }}" class="mt-3 max-h-72 rounded-lg border border-gray-100">
                            </div>
                        @endif
                        @if (Setting::get('qris_note'))
                            <p class="mt-3 whitespace-pre-line text-sm text-gray-600">{{ Setting::get('qris_note') }}</p>
                        @else
                            <p class="mt-3 text-sm text-gray-600">Silakan hubungi admin melalui WhatsApp untuk mendapatkan QRIS pembayaran.</p>
                        @endif

                        <form method="POST" action="{{ route('orders.proof', $order->tracking_token) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                            @csrf
                            <div>
                                <x-input-label for="proof" value="Unggah bukti pembayaran" />
                                <input id="proof" name="proof" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100" required>
                                <p class="mt-1 text-xs text-gray-500">JPG, PNG, atau WEBP maksimal 5 MB.</p>
                            </div>
                            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Kirim Bukti</button>
                        </form>
                    @else
                        <p class="mt-3 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-700">Pembayaran dilakukan saat pesanan diterima. Admin akan menghubungi Anda untuk konfirmasi.</p>
                    @endif
                @endif
            </section>
        </div>

        <aside class="space-y-4">
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Data Pengiriman</h2>
                <div class="mt-3 space-y-2 text-sm text-gray-600">
                    <p class="font-medium text-gray-900">{{ $order->customer_name }}</p>
                    <p>{{ $order->customer_phone }}</p>
                    @if ($order->customer_address)
                        <p class="whitespace-pre-line">{{ $order->customer_address }}</p>
                    @endif
                    @if ($order->customer_note)
                        <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs">Catatan: {{ $order->customer_note }}</p>
                    @endif
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Bantuan</h2>
                <p class="mt-1 text-sm text-gray-500">Simpan tautan halaman ini untuk memantau status pesanan.</p>
                @if ($adminWa)
                    <a href="https://wa.me/{{ $adminWa }}?text={{ $waText }}" target="_blank" rel="noopener" class="mt-4 block rounded-xl bg-green-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-green-700">Hubungi via WhatsApp</a>
                @endif
                <a href="{{ route('track.form') }}" class="mt-2 block rounded-xl border border-gray-300 px-4 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50">Lacak pesanan lain</a>
            </section>
        </aside>
    </div>
</x-store-layout>
