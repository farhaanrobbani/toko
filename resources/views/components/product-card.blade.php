@props(['product', 'available' => null])

@php
    $stock = $available ?? max(0, (float) ($product->stock_qty ?? 0) - (float) ($product->reserved_qty ?? 0));
    $inStock = $stock > 0;
@endphp

<div class="group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <a href="{{ route('store.products.show', $product) }}" class="block aspect-[4/3] overflow-hidden bg-gray-100">
        @if ($product->image_path)
            <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-indigo-50 via-white to-amber-50 text-4xl font-bold text-indigo-200">
                {{ mb_substr($product->name, 0, 1) }}
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-indigo-600">{{ $product->category?->name ?? 'Produk' }}</p>
        <a href="{{ route('store.products.show', $product) }}" class="mt-1 line-clamp-2 font-semibold text-gray-900 hover:text-indigo-700">{{ $product->name }}</a>
        <p class="mt-1 text-xs text-gray-500">{{ $product->unit }}</p>

        <div class="mt-auto flex items-end justify-between gap-2 pt-4">
            <div>
                <p class="text-lg font-bold text-gray-900">{{ rupiah($product->sell_price) }}</p>
                <p class="text-xs {{ $inStock ? 'text-green-600' : 'text-red-500' }}">
                    {{ $inStock ? 'Stok '.qty($stock) : 'Stok habis' }}
                </p>
            </div>

            @if ($inStock)
                <form method="POST" action="{{ route('cart.store', $product) }}">
                    @csrf
                    <button type="submit" class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">+ Keranjang</button>
                </form>
            @else
                <span class="rounded-xl bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-500">Habis</span>
            @endif
        </div>
    </div>
</div>
