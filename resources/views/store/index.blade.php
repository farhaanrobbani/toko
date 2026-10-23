@php
    $storeName = \App\Models\Setting::get('store_name', 'Toko');
@endphp

<x-store-layout>
    <section class="overflow-hidden rounded-3xl bg-gray-900 px-6 py-10 text-white sm:px-10 sm:py-14">
        <div class="max-w-2xl">
            <p class="text-sm font-semibold uppercase tracking-widest text-indigo-300">Belanja online</p>
            <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Kebutuhan harian, langsung dari {{ $storeName }}.</h1>
            <p class="mt-3 text-sm leading-relaxed text-gray-300 sm:text-base">Pesan tanpa perlu membuat akun. Bayar dengan QRIS atau COD, lalu pantau pesanan lewat tautan yang kami berikan.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="#produk" class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-gray-100">Mulai belanja</a>
                <a href="{{ route('track.form') }}" class="rounded-xl border border-white/30 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10">Lacak pesanan</a>
            </div>
        </div>
    </section>

    <section id="produk" class="mt-10">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">Katalog Produk</h2>
                <p class="mt-1 text-sm text-gray-500">
                    @if ($warehouse)
                        Dikirim dari {{ $warehouse->name }}.
                    @else
                        Belum ada lokasi stok aktif.
                    @endif
                </p>
            </div>

            <form method="GET" class="flex w-full gap-2 sm:w-auto">
                @if (request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari produk..." class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-64">
                <button type="submit" class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Cari</button>
            </form>
        </div>

        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('home', request()->only('q')) }}" class="rounded-full px-4 py-1.5 text-sm font-medium {{ request()->filled('category') ? 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50' : 'bg-gray-900 text-white' }}">Semua</a>
            @foreach ($categories as $category)
                @if ($category->products_count > 0)
                    <a href="{{ route('home', array_filter(['category' => $category->id, 'q' => request('q')])) }}" class="rounded-full px-4 py-1.5 text-sm font-medium {{ (string) request('category') === (string) $category->id ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50' }}">
                        {{ $category->name }} <span class="text-xs opacity-70">{{ $category->products_count }}</span>
                    </a>
                @endif
            @endforeach
        </div>

        @if ($products->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
                <p class="font-semibold text-gray-900">Produk tidak ditemukan.</p>
                <p class="mt-1 text-sm text-gray-500">Coba kata kunci atau kategori lain.</p>
                <a href="{{ route('home') }}" class="mt-4 inline-block rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Lihat semua produk</a>
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </section>
</x-store-layout>
