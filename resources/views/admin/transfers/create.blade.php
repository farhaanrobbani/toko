<x-admin-layout title="Transfer Baru">
    @php
        $defaultFrom = $warehouses->firstWhere('is_default', true)?->id ?? $warehouses->first()?->id;
    @endphp

    <form
        method="POST"
        action="{{ route('admin.transfers.store') }}"
        class="space-y-5"
        x-data="{
            fromWarehouseId: '{{ old('from_warehouse_id', $defaultFrom) }}',
            toWarehouseId: '{{ old('to_warehouse_id') }}',
            products: {{ Js::from($products) }},
            stockLevels: {{ Js::from($stockLevels) }},
            items: {{ Js::from(old('items', [['product_id' => '', 'quantity' => 1]])) }},
            addItem() {
                this.items.push({ product_id: '', quantity: 1 });
            },
            removeItem(index) {
                if (this.items.length > 1) this.items.splice(index, 1);
            },
            available(productId) {
                const levels = this.stockLevels[this.fromWarehouseId] || {};
                return Number(levels[productId] || 0);
            },
            overStock(item) {
                return Number(item.quantity) > this.available(item.product_id);
            },
        }"
    >
        @csrf

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Rute Transfer</h2>
                <p class="text-sm text-gray-500">Stok berkurang saat barang dikirim dan bertambah saat diterima.</p>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="from_warehouse_id" value="Dari Lokasi" />
                    <select id="from_warehouse_id" name="from_warehouse_id" x-model="fromWarehouseId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="to_warehouse_id" value="Ke Lokasi" />
                    <select id="to_warehouse_id" name="to_warehouse_id" x-model="toWarehouseId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="">Pilih lokasi tujuan</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="border-t border-gray-100 px-5 py-4">
                <x-input-label for="note" value="Catatan" />
                <textarea id="note" name="note" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('note') }}</textarea>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-gray-900">Produk yang Dipindahkan</h2>
                    <p class="text-sm text-gray-500">Stok tersedia mengikuti lokasi asal.</p>
                </div>
                <button type="button" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="addItem()">Tambah Baris</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Produk</th>
                            <th class="px-5 py-3 text-right">Tersedia</th>
                            <th class="w-40 px-5 py-3">Jumlah Transfer</th>
                            <th class="w-16 px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(item, index) in items" :key="index">
                            <tr>
                                <td class="px-5 py-3">
                                    <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="">Pilih produk</option>
                                        <template x-for="product in products" :key="product.id">
                                            <option :value="product.id" x-text="product.name + ' (' + product.sku + ')'"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-5 py-3 text-right text-gray-700" x-text="item.product_id ? available(item.product_id) : '—'"></td>
                                <td class="px-5 py-3">
                                    <input :name="'items[' + index + '][quantity]'" x-model="item.quantity" type="number" step="0.001" min="0.001" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :class="overStock(item) ? 'border-red-400 text-red-700' : ''" required>
                                    <p x-show="overStock(item)" class="mt-1 text-xs text-red-600">Melebihi stok tersedia.</p>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <button type="button" class="text-xs font-medium text-red-600 hover:text-red-700" @click="removeItem(index)" x-show="items.length > 1">Hapus</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Buat Transfer</button>
            <a href="{{ route('admin.transfers.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Batal</a>
        </div>
    </form>
</x-admin-layout>
