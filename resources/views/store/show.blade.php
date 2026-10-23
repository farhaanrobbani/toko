@php
    $wa = wa_number(\App\Models\Setting::get('admin_whatsapp') ?: \App\Models\Setting::get('store_phone'));
    $waText = rawurlencode("Halo, saya ingin bertanya tentang produk {$product->name}.");
@endphp

<x-store-layout :title="$product->name">
    <nav class="text-sm text-gray-500">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Belanja</a>
        <span class="mx-2">/</span>
        @if ($product->category)
            <a href="{{ route('home', ['category' => $product->category_id]) }}" class="hover:text-indigo-600">{{ $product->category->name }}</a>
            <span class="mx-2">/</span>
        @endif
        <span class="text-gray-700">{{ $product->name }}</span>
    </nav>

    <div class="mt-6 grid gap-8 lg:grid-cols-2">
        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
            @if ($product->image_path)
                <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="aspect-square w-full object-cover">
            @else
                <div class="flex aspect-square w-full items-center justify-center bg-gradient-to-br from-indigo-50 via-white to-amber-50 text-7xl font-bold text-indigo-200">
                    {{ mb_substr($product->name, 0, 1) }}
                </div>
            @endif
        </div>

        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">{{ $product->category?->name ?? 'Produk' }}</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900">{{ $product->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">SKU {{ $product->sku }} · Satuan {{ $product->unit }}</p>

            <p class="mt-5 text-3xl font-bold text-gray-900">{{ rupiah($product->sell_price) }}</p>
            <p class="mt-1 text-sm {{ $available > 0 ? 'text-green-600' : 'text-red-500' }}">
                {{ $available > 0 ? 'Stok tersedia '.qty($available).' '.$product->unit : 'Stok habis' }}
            </p>

            @if ($product->description)
                <div class="mt-5 text-sm leading-relaxed text-gray-600">{{ $product->description }}</div>
            @endif

            <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                @if ($available > 0)
                    <form method="POST" action="{{ route('cart.store', $product) }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <x-input-label for="quantity" value="Jumlah ({{ $product->unit }})" class="text-xs" />
                            <input id="quantity" name="quantity" type="number" step="0.001" min="0.001" max="{{ $available }}" value="{{ old('quantity', 1) }}" class="mt-1 w-32 rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Tambah ke Keranjang</button>
                    </form>
                @else
                    <p class="text-sm font-medium text-red-600">Produk ini sedang tidak tersedia.</p>
                @endif

                @if ($wa)
                    <a href="https://wa.me/{{ $wa }}?text={{ $waText }}" target="_blank" rel="noopener" class="mt-4 inline-block text-sm font-medium text-gray-600 hover:text-indigo-600">Tanya produk via WhatsApp &rarr;</a>
                @endif
            </div>

            <div class="mt-5 grid gap-3 text-sm text-gray-600 sm:grid-cols-3">
                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-200">
                    <p class="font-semibold text-gray-900">Guest checkout</p>
                    <p class="mt-1 text-xs">Tanpa daftar akun.</p>
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-200">
                    <p class="font-semibold text-gray-900">QRIS / COD</p>
                    <p class="mt-1 text-xs">Pilih saat checkout.</p>
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-200">
                    <p class="font-semibold text-gray-900">Lacak pesanan</p>
                    <p class="mt-1 text-xs">Lewat tautan pesanan.</p>
                </div>
            </div>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="mt-12">
            <h2 class="text-xl font-bold tracking-tight text-gray-900">Produk serupa</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($related as $item)
                    <x-product-card :product="$item" />
                @endforeach
            </div>
        </section>
    @endif
</x-store-layout>
