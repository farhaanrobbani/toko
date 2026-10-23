<x-store-layout title="Keranjang">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Keranjang</h1>
            <p class="mt-1 text-sm text-gray-500">Periksa jumlah sebelum melanjutkan ke checkout.</p>
        </div>
        <a href="{{ route('home') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">&larr; Lanjut belanja</a>
    </div>

    @if ($lines->isEmpty())
        <div class="mt-8 rounded-3xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
            <p class="text-lg font-semibold text-gray-900">Keranjang masih kosong.</p>
            <p class="mt-1 text-sm text-gray-500">Pilih produk dari katalog untuk memulai.</p>
            <a href="{{ route('home') }}" class="mt-5 inline-block rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Lihat produk</a>
        </div>
    @else
        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_340px]">
            <div class="space-y-3">
                @foreach ($lines as $line)
                    @php($product = $line['product'])
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-start gap-4">
                            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-gray-100">
                                @if ($product->image_path)
                                    <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-xl font-bold text-indigo-200">{{ mb_substr($product->name, 0, 1) }}</div>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('store.products.show', $product) }}" class="font-semibold text-gray-900 hover:text-indigo-700">{{ $product->name }}</a>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $product->sku }} · {{ $product->unit }}</p>
                                <p class="mt-1 text-sm font-medium text-gray-900">{{ rupiah($product->sell_price) }}</p>
                                @if ($line['insufficient'])
                                    <p class="mt-1 text-xs font-medium text-red-600">Stok tersedia hanya {{ qty($line['available']) }}.</p>
                                @endif
                            </div>

                            <div class="flex items-end gap-3">
                                <form method="POST" action="{{ route('cart.update', $product) }}" class="flex items-end gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <div>
                                        <x-input-label for="quantity-{{ $product->id }}" value="Jumlah" class="text-xs" />
                                        <input id="quantity-{{ $product->id }}" name="quantity" type="number" step="0.001" min="0" value="{{ qty($line['quantity']) }}" class="mt-1 w-24 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">Perbarui</button>
                                </form>

                                <form method="POST" action="{{ route('cart.destroy', $product) }}" onsubmit="return confirm('Hapus produk ini dari keranjang?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="pb-2 text-xs font-medium text-red-600 hover:text-red-700">Hapus</button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-3 flex justify-between border-t border-gray-100 pt-3 text-sm">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="font-semibold text-gray-900">{{ rupiah($line['subtotal']) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <aside class="h-fit rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:sticky lg:top-24">
                <h2 class="font-semibold text-gray-900">Ringkasan</h2>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span>{{ rupiah($subtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Ongkir</span>
                        <span class="text-xs">Dikonfirmasi admin</span>
                    </div>
                </div>
                <div class="mt-4 flex justify-between border-t border-gray-100 pt-4 text-base font-semibold text-gray-900">
                    <span>Total sementara</span>
                    <span>{{ rupiah($subtotal) }}</span>
                </div>

                @if ($lines->contains('insufficient', true))
                    <p class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">Perbarui jumlah produk yang stoknya tidak mencukupi sebelum checkout.</p>
                    <span class="mt-4 block cursor-not-allowed rounded-xl bg-gray-200 px-5 py-3 text-center text-sm font-semibold text-gray-500">Lanjut ke Checkout</span>
                @else
                    <a href="{{ route('checkout.create') }}" class="mt-4 block rounded-xl bg-indigo-600 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-700">Lanjut ke Checkout</a>
                @endif

                <p class="mt-3 text-center text-xs text-gray-500">Tidak perlu membuat akun.</p>
            </aside>
        </div>
    @endif
</x-store-layout>
