<x-admin-layout title="Pembelian Baru">
    <form
        method="POST"
        action="{{ route('admin.purchases.store') }}"
        class="space-y-5"
        x-data="{
            products: {{ Js::from($products) }},
            items: {{ Js::from(old('items', [['product_id' => '', 'quantity' => 1, 'unit_cost' => '']])) }},
            addItem() {
                this.items.push({ product_id: '', quantity: 1, unit_cost: '' });
            },
            removeItem(index) {
                if (this.items.length > 1) this.items.splice(index, 1);
            },
            product(id) {
                return this.products.find((product) => String(product.id) === String(id));
            },
            fillCost(index) {
                const product = this.product(this.items[index].product_id);
                if (product) this.items[index].unit_cost = product.cost_price;
            },
            lineTotal(item) {
                return (Number(item.quantity) || 0) * (Number(item.unit_cost) || 0);
            },
            total() {
                return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
            },
            format(value) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(value || 0));
            },
        }"
    >
        @csrf

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Informasi Pembelian</h2>
                <p class="text-sm text-gray-500">Stok langsung masuk ke lokasi yang dipilih.</p>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <x-input-label for="purchased_at" value="Tanggal" />
                    <x-text-input id="purchased_at" name="purchased_at" type="date" class="mt-1 block w-full" :value="old('purchased_at', now()->toDateString())" required />
                </div>
                <div>
                    <x-input-label for="warehouse_id" value="Lokasi Tujuan" />
                    <select id="warehouse_id" name="warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $warehouse->is_default ? $warehouse->id : '') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="supplier_id" value="Supplier" />
                    <select id="supplier_id" name="supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Tanpa supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="invoice_number" value="No. Invoice" />
                    <x-text-input id="invoice_number" name="invoice_number" class="mt-1 block w-full" :value="old('invoice_number')" placeholder="Opsional" />
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
                    <h2 class="font-semibold text-gray-900">Item Pembelian</h2>
                    <p class="text-sm text-gray-500">HPP produk akan dihitung ulang otomatis.</p>
                </div>
                <button type="button" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="addItem()">Tambah Baris</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Produk</th>
                            <th class="w-36 px-5 py-3">Jumlah</th>
                            <th class="w-44 px-5 py-3">Harga Beli / Unit</th>
                            <th class="w-40 px-5 py-3 text-right">Subtotal</th>
                            <th class="w-16 px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(item, index) in items" :key="index">
                            <tr>
                                <td class="px-5 py-3">
                                    <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" @change="fillCost(index)" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="">Pilih produk</option>
                                        <template x-for="product in products" :key="product.id">
                                            <option :value="product.id" x-text="product.name + ' (' + product.sku + ')'"></option>
                                        </template>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500" x-text="product(item.product_id) ? product(item.product_id).unit : ''"></p>
                                </td>
                                <td class="px-5 py-3">
                                    <input :name="'items[' + index + '][quantity]'" x-model="item.quantity" type="number" step="0.001" min="0.001" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </td>
                                <td class="px-5 py-3">
                                    <input :name="'items[' + index + '][unit_cost]'" x-model="item.unit_cost" type="number" step="0.01" min="0" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </td>
                                <td class="px-5 py-3 text-right font-medium text-gray-900" x-text="format(lineTotal(item))"></td>
                                <td class="px-5 py-3 text-right">
                                    <button type="button" class="text-xs font-medium text-red-600 hover:text-red-700" @click="removeItem(index)" x-show="items.length > 1">Hapus</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-5 py-3 text-right text-sm font-semibold text-gray-700">Total</td>
                            <td class="px-5 py-3 text-right text-base font-semibold text-gray-900" x-text="format(total())"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan Pembelian</button>
            <a href="{{ route('admin.purchases.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Batal</a>
        </div>
    </form>
</x-admin-layout>
