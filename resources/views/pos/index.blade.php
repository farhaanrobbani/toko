@php
    use App\Enums\PosPaymentMethod;
    use App\Enums\SaleStatus;

    $paymentMethods = collect(PosPaymentMethod::cases())
        ->map(fn (PosPaymentMethod $method) => ['value' => $method->value, 'label' => $method->label()])
        ->all();
@endphp

<x-pos-layout title="POS">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Kasir</h2>
            <p class="text-sm text-gray-500">Pilih produk, atur pembayaran, lalu cetak struk.</p>
        </div>

        <form method="GET" action="{{ route('pos.index') }}" class="flex items-center gap-2">
            <label for="warehouse" class="text-sm font-medium text-gray-600">Lokasi</label>
            <select id="warehouse" name="warehouse" onchange="this.form.submit()" class="rounded-lg border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($warehouses as $option)
                    <option value="{{ $option->id }}" @selected($warehouse?->id === $option->id)>{{ $option->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if (! $warehouse)
        <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50 p-8 text-center text-sm text-amber-800">
            Belum ada lokasi stok aktif. Tambahkan lokasi stok terlebih dahulu.
        </div>
    @else
        <div
            x-data="posScreen({
                products: {{ Js::from($products) }},
                oldItems: {{ Js::from($oldItems) }},
                paymentMethods: {{ Js::from($paymentMethods) }},
                initialPaymentMethod: {{ Js::from(old('payment_method', 'cash')) }},
                initialDiscount: {{ Js::from((float) old('discount', 0)) }},
                initialPaid: {{ Js::from((float) old('paid', 0)) }},
                initialCustomerName: {{ Js::from(old('customer_name', '')) }},
                initialCustomerPhone: {{ Js::from(old('customer_phone', '')) }},
                initialNote: {{ Js::from(old('note', '')) }}
            })"
            @keydown.window.f2.prevent="$refs.search.focus()"
            class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]"
        >
            <div class="space-y-5">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1">
                            <svg class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z" />
                            </svg>
                            <input
                                x-ref="search"
                                x-model.debounce.150ms="search"
                                @keydown.enter.prevent="scanOrAdd()"
                                type="search"
                                placeholder="Cari nama / SKU / scan barcode (F2)"
                                class="block w-full rounded-lg border-gray-300 py-2 pl-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>
                        <p class="text-xs text-gray-500">
                            <span x-text="filteredProducts.length"></span> produk · stok <span x-text="formatQty(totalStock)"></span>
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-3">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <button
                            type="button"
                            @click="add(product)"
                            :disabled="product.stock <= 0"
                            class="flex min-h-28 flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:translate-y-0"
                        >
                            <div>
                                <p class="line-clamp-2 text-sm font-semibold text-gray-900" x-text="product.name"></p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    <span x-text="product.sku"></span>
                                    <span x-show="product.barcode"> · <span x-text="product.barcode"></span></span>
                                </p>
                            </div>
                            <div class="mt-3 flex items-end justify-between gap-2">
                                <p class="text-base font-bold text-gray-900" x-text="rp(product.price)"></p>
                                <p class="text-xs font-medium" :class="product.stock > 0 ? 'text-gray-500' : 'text-red-600'">
                                    Stok <span x-text="formatQty(product.stock)"></span> <span x-text="product.unit"></span>
                                </p>
                            </div>
                        </button>
                    </template>

                    <div x-show="filteredProducts.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500 sm:col-span-2 2xl:col-span-3">
                        Produk tidak ditemukan.
                    </div>
                </div>

                @if ($recentSales->isNotEmpty())
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-5 py-4">
                            <h3 class="font-semibold text-gray-900">Transaksi Terakhir</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="px-5 py-3">Nomor</th>
                                        <th class="px-5 py-3">Waktu</th>
                                        <th class="px-5 py-3">Kasir</th>
                                        <th class="px-5 py-3 text-right">Total</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($recentSales as $sale)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-5 py-3">
                                                <a href="{{ route('pos.receipt', $sale) }}" class="font-medium text-indigo-600 hover:text-indigo-700">{{ $sale->sale_number }}</a>
                                            </td>
                                            <td class="px-5 py-3 text-gray-600">{{ $sale->created_at->format('d/m H:i') }}</td>
                                            <td class="px-5 py-3 text-gray-600">{{ $sale->cashier?->name ?? '—' }}</td>
                                            <td class="px-5 py-3 text-right font-medium text-gray-900">{{ rupiah($sale->total) }}</td>
                                            <td class="px-5 py-3">
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $sale->status->badgeClass() }}">{{ $sale->status->label() }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <form method="POST" action="{{ route('pos.store') }}" class="xl:sticky xl:top-5 xl:self-start">
                @csrf
                <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                <input type="hidden" name="items" :value="itemsJson">

                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                        <h3 class="font-semibold text-gray-900">Keranjang</h3>
                        <button type="button" x-show="cart.length > 0" @click="clearCart()" class="text-xs font-medium text-red-600 hover:text-red-700">Kosongkan</button>
                    </div>

                    <div class="max-h-[45vh] divide-y divide-gray-100 overflow-y-auto">
                        <template x-for="item in cart" :key="item.id">
                            <div class="px-4 py-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-gray-900" x-text="item.name"></p>
                                        <p class="text-xs text-gray-500"><span x-text="rp(item.price)"></span> / <span x-text="item.unit"></span></p>
                                    </div>
                                    <button type="button" @click="remove(item)" class="text-gray-400 hover:text-red-600" aria-label="Hapus">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <div class="flex items-center rounded-lg border border-gray-300">
                                        <button type="button" @click="decrease(item)" class="px-2 py-1 text-gray-600 hover:bg-gray-50">−</button>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.001"
                                            :max="item.stock"
                                            :value="item.quantity"
                                            @change="updateQty(item, $event.target.value)"
                                            class="w-16 border-x border-gray-300 px-1 py-1 text-center text-sm focus:border-indigo-500 focus:ring-0"
                                        >
                                        <button type="button" @click="increase(item)" class="px-2 py-1 text-gray-600 hover:bg-gray-50">+</button>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900" x-text="rp(item.price * item.quantity)"></p>
                                </div>
                            </div>
                        </template>

                        <div x-show="cart.length === 0" class="px-5 py-10 text-center text-sm text-gray-500">
                            Keranjang kosong. Klik produk untuk menambahkan.
                        </div>
                    </div>

                    <div class="space-y-3 border-t border-gray-200 px-5 py-4 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span class="font-medium text-gray-900" x-text="rp(subtotal)"></span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <label for="discount" class="text-gray-600">Diskon</label>
                            <input id="discount" name="discount" type="number" min="0" step="100" x-model.number="discount" @input="refreshPaid()" class="w-32 rounded-lg border-gray-300 py-1.5 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="flex justify-between border-t border-dashed border-gray-200 pt-3 text-base">
                            <span class="font-semibold text-gray-900">Total</span>
                            <span class="font-bold text-gray-900" x-text="rp(total)"></span>
                        </div>

                        <div>
                            <label for="payment_method" class="block text-gray-600">Pembayaran</label>
                            <select id="payment_method" name="payment_method" x-model="paymentMethod" @change="syncPaid()" class="mt-1 block w-full rounded-lg border-gray-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <template x-for="method in paymentMethods" :key="method.value">
                                    <option :value="method.value" x-text="method.label" :selected="method.value === paymentMethod"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label for="paid" class="block text-gray-600">Dibayar</label>
                            <input id="paid" name="paid" type="number" min="0" step="100" x-model.number="paid" :readonly="paymentMethod !== 'cash'" class="mt-1 block w-full read-only:bg-gray-50 rounded-lg border-gray-300 py-2 text-right text-base font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button type="button" @click="paid = total" class="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50">Uang pas</button>
                                <button type="button" @click="paid = Math.ceil(total / 50000) * 50000" x-show="total > 0" class="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50" x-text="rp(Math.ceil(total / 50000) * 50000)"></button>
                                <button type="button" @click="paid = Math.ceil(total / 100000) * 100000" x-show="total > 0" class="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50" x-text="rp(Math.ceil(total / 100000) * 100000)"></button>
                            </div>
                        </div>

                        <div class="flex justify-between rounded-lg bg-gray-50 px-3 py-2">
                            <span class="text-gray-600">Kembalian</span>
                            <span class="font-semibold" :class="change < 0 ? 'text-red-600' : 'text-green-700'" x-text="rp(Math.max(0, change))"></span>
                        </div>

                        <details class="rounded-lg border border-gray-200 px-3 py-2">
                            <summary class="cursor-pointer text-xs font-medium text-gray-600">Pelanggan & catatan (opsional)</summary>
                            <div class="mt-3 space-y-2">
                                <input name="customer_name" type="text" x-model="customerName" placeholder="Nama pelanggan" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <input name="customer_phone" type="text" x-model="customerPhone" placeholder="Nomor telepon" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <textarea name="note" rows="2" x-model="note" placeholder="Catatan transaksi" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                        </details>

                        <p x-show="change < 0" class="text-xs font-medium text-red-600">Nominal pembayaran masih kurang.</p>

                        <button
                            type="submit"
                            :disabled="cart.length === 0 || total <= 0 || change < 0"
                            class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                        >
                            Simpan Pembayaran
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <script>
        function posScreen(config) {
            return {
                products: config.products,
                oldItems: config.oldItems,
                paymentMethods: config.paymentMethods,
                search: '',
                cart: [],
                paymentMethod: config.initialPaymentMethod || 'cash',
                discount: Number(config.initialDiscount) || 0,
                paid: Number(config.initialPaid) || 0,
                customerName: config.initialCustomerName || '',
                customerPhone: config.initialCustomerPhone || '',
                note: config.initialNote || '',

                init() {
                    this.oldItems.forEach((row) => {
                        const product = this.products.find((item) => item.id === Number(row.product_id));

                        if (!product || product.stock <= 0) {
                            return;
                        }

                        const existing = this.cart.find((item) => item.id === product.id);

                        if (existing) {
                            existing.quantity = Math.min(existing.quantity + Number(row.quantity || 1), product.stock);
                        } else {
                            this.cart.push({ ...product, quantity: Math.min(Number(row.quantity || 1), product.stock) });
                        }
                    });

                    if (this.cart.length > 0 && this.paid <= 0) {
                        this.paid = this.total;
                    }
                },

                get filteredProducts() {
                    const term = this.search.trim().toLowerCase();

                    if (!term) {
                        return this.products;
                    }

                    return this.products.filter((product) =>
                        product.name.toLowerCase().includes(term)
                        || product.sku.toLowerCase().includes(term)
                        || (product.barcode || '').toLowerCase().includes(term)
                    );
                },

                get totalStock() {
                    return this.products.reduce((sum, product) => sum + product.stock, 0);
                },

                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                },

                get total() {
                    return Math.max(0, this.subtotal - (Number(this.discount) || 0));
                },

                get change() {
                    return (Number(this.paid) || 0) - this.total;
                },

                get itemsJson() {
                    return JSON.stringify(this.cart.map((item) => ({
                        product_id: item.id,
                        quantity: item.quantity
                    })));
                },

                add(product) {
                    if (product.stock <= 0) {
                        return;
                    }

                    const existing = this.cart.find((item) => item.id === product.id);

                    if (existing) {
                        existing.quantity = Math.min(existing.quantity + 1, product.stock);
                    } else {
                        this.cart.push({ ...product, quantity: 1 });
                    }

                    this.refreshPaid();
                },

                remove(item) {
                    this.cart = this.cart.filter((row) => row.id !== item.id);
                    this.refreshPaid();
                },

                increase(item) {
                    item.quantity = Math.min(item.quantity + 1, item.stock);
                    this.refreshPaid();
                },

                decrease(item) {
                    if (item.quantity <= 1) {
                        this.remove(item);

                        return;
                    }

                    item.quantity = Math.max(1, item.quantity - 1);
                    this.refreshPaid();
                },

                updateQty(item, value) {
                    const quantity = Math.min(Math.max(Number(value) || 0, 0), item.stock);

                    if (quantity <= 0) {
                        this.remove(item);

                        return;
                    }

                    item.quantity = quantity;
                    this.refreshPaid();
                },

                clearCart() {
                    this.cart = [];
                    this.paid = 0;
                    this.discount = 0;
                },

                scanOrAdd() {
                    const term = this.search.trim().toLowerCase();

                    if (!term) {
                        return;
                    }

                    const exact = this.products.find((product) =>
                        (product.barcode || '').toLowerCase() === term || product.sku.toLowerCase() === term
                    );

                    if (exact) {
                        this.add(exact);
                        this.search = '';

                        return;
                    }

                    if (this.filteredProducts.length === 1) {
                        this.add(this.filteredProducts[0]);
                        this.search = '';
                    }
                },

                syncPaid() {
                    this.refreshPaid();
                },

                refreshPaid() {
                    if (this.paymentMethod !== 'cash') {
                        this.paid = this.total;
                    }
                },

                rp(value) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0
                    }).format(Number(value) || 0);
                },

                formatQty(value) {
                    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 }).format(Number(value) || 0);
                }
            };
        }
    </script>
</x-pos-layout>
