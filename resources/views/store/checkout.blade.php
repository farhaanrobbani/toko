@php
    $selectedDelivery = old('delivery_method', \App\Enums\DeliveryMethod::Delivery->value);
    $selectedPayment = old('payment_method', \App\Enums\PaymentMethod::Qris->value);
    $checkoutLines = $lines->map(fn ($line) => [
        'product_id' => $line['product']->getKey(),
        'quantity' => $line['quantity'],
        'available' => $line['available'],
    ])->values();
@endphp

<x-store-layout title="Checkout">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Checkout</h1>
            <p class="mt-1 text-sm text-gray-500">Isi data pengiriman dan pilih metode pembayaran.</p>
        </div>
        <a href="{{ route('cart.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">&larr; Kembali ke keranjang</a>
    </div>

    <form
        method="POST"
        action="{{ route('checkout.store') }}"
        class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]"
        x-data="checkoutStock('{{ $selectedDelivery }}', @js($checkoutLines))"
    >
        @csrf

        <div class="space-y-5">
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Data Pemesan</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="customer_name" value="Nama lengkap" />
                        <x-text-input id="customer_name" name="customer_name" class="mt-1 block w-full" :value="old('customer_name')" required autocomplete="name" />
                    </div>
                    <div>
                        <x-input-label for="customer_phone" value="Nomor WhatsApp / telepon" />
                        <x-text-input id="customer_phone" name="customer_phone" class="mt-1 block w-full" :value="old('customer_phone')" placeholder="08xxxxxxxxxx" required autocomplete="tel" />
                    </div>
                </div>

                <div class="mt-4">
                    <x-input-label value="Metode pengambilan" />
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        @foreach (\App\Enums\DeliveryMethod::cases() as $method)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-4 hover:border-indigo-300">
                                <input type="radio" name="delivery_method" value="{{ $method->value }}" x-model="delivery" @checked($selectedDelivery === $method->value) class="mt-1 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>
                                    <span class="block text-sm font-semibold text-gray-900">{{ $method->label() }}</span>
                                    <span class="mt-0.5 block text-xs text-gray-500">{{ $method === \App\Enums\DeliveryMethod::Delivery ? 'Alamat diisi, ongkir dikonfirmasi admin.' : 'Ambil sendiri di toko.' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4" x-show="delivery === 'delivery'" x-cloak>
                    <x-input-label for="customer_address" value="Alamat pengiriman" />
                    <textarea id="customer_address" name="customer_address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :required="delivery === 'delivery'" placeholder="Nama jalan, nomor rumah, kelurahan, kecamatan, kota">{{ old('customer_address') }}</textarea>
                </div>

                <div class="mt-4">
                    <x-input-label for="customer_note" value="Catatan pesanan" />
                    <textarea id="customer_note" name="customer_note" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: antar sore hari">{{ old('customer_note') }}</textarea>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Metode Pembayaran</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach (\App\Enums\PaymentMethod::cases() as $method)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-4 hover:border-indigo-300">
                            <input type="radio" name="payment_method" value="{{ $method->value }}" @checked($selectedPayment === $method->value) class="mt-1 border-gray-300 text-indigo-600 focus:ring-indigo-500" required>
                            <span>
                                <span class="block text-sm font-semibold text-gray-900">{{ $method->label() }}</span>
                                <span class="mt-0.5 block text-xs text-gray-500">{{ $method === \App\Enums\PaymentMethod::Qris ? 'Scan QRIS lalu unggah bukti.' : 'Bayar saat pesanan diterima.' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <label class="flex items-start gap-3 text-sm text-gray-700">
                    <input type="checkbox" name="agree" value="1" @checked(old('agree')) class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" required>
                    <span>Saya setuju pesanan diproses sesuai data di atas dan ongkos kirim akan dikonfirmasi admin via WhatsApp.</span>
                </label>
            </section>
        </div>

        <aside class="h-fit rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:sticky lg:top-24">
            <h2 class="font-semibold text-gray-900">Pesanan Anda</h2>
            <div class="mt-4 space-y-3">
                @foreach ($lines as $line)
                    <div class="flex justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-gray-900">{{ $line['product']->name }}</p>
                            <p class="text-xs text-gray-500">{{ qty($line['quantity']) }} {{ $line['product']->unit }} × {{ rupiah($line['product']->sell_price) }}</p>
                            <template x-if="insufficientFor({{ $line['product']->getKey() }})">
                                <p class="mt-1 text-xs font-medium text-red-600">
                                    Stok tersedia hanya <span x-text="fmt(availableFor({{ $line['product']->getKey() }}))">{{ qty($line['available']) }}</span>.
                                </p>
                            </template>
                        </div>
                        <p class="shrink-0 font-medium text-gray-900">{{ rupiah($line['subtotal']) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>{{ rupiah($subtotal) }}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Ongkir</span>
                    <span class="text-xs">Dikonfirmasi admin</span>
                </div>
                <div class="flex justify-between pt-2 text-base font-semibold text-gray-900">
                    <span>Total sementara</span>
                    <span>{{ rupiah($subtotal) }}</span>
                </div>
            </div>

            <template x-if="anyInsufficient">
                <div class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">
                    Stok berubah dan jumlah pesanan tidak lagi tersedia.
                    <a href="{{ route('cart.index') }}" class="font-semibold underline">Kembali ke keranjang</a>.
                </div>
            </template>

            <button
                type="submit"
                class="mt-5 w-full rounded-xl px-5 py-3 text-sm font-semibold"
                :disabled="anyInsufficient"
                :class="anyInsufficient ? 'cursor-not-allowed bg-gray-200 text-gray-500' : 'bg-indigo-600 text-white hover:bg-indigo-700'"
            >
                <span x-text="anyInsufficient ? 'Stok Berubah' : 'Buat Pesanan'">Buat Pesanan</span>
            </button>
            <p class="mt-3 text-center text-xs text-gray-500">Stok akan direservasi setelah pesanan dibuat.</p>
        </aside>
    </form>
</x-store-layout>
