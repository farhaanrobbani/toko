<x-admin-layout title="Penyesuaian Stok">
    <form
        method="POST"
        action="{{ route('admin.adjustments.store') }}"
        class="max-w-2xl space-y-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
        x-data="{
            products: {{ Js::from($products->map(fn ($product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'unit' => $product->unit])->values()) }},
            get selectedProduct() {
                return this.products.find((product) => String(product.id) === String(this.productId));
            },
            productId: '{{ old('product_id', request('product')) }}',
        }"
    >
        @csrf

        <div>
            <h2 class="font-semibold text-gray-900">Catat Penyesuaian</h2>
            <p class="text-sm text-gray-500">Gunakan untuk koreksi kecil di luar pembelian, transfer, dan opname.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="warehouse_id" value="Lokasi" />
                <select id="warehouse_id" name="warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', request('warehouse', $warehouse->is_default ? $warehouse->id : '')) === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="product_id" value="Produk" />
                <select id="product_id" name="product_id" x-model="productId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    <option value="">Pilih produk</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500" x-show="selectedProduct" x-text="selectedProduct ? 'Satuan: ' + selectedProduct.unit : ''"></p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="direction" value="Arah" />
                <select id="direction" name="direction" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    <option value="in" @selected(old('direction') === 'in')>Tambah stok (+)</option>
                    <option value="out" @selected(old('direction') === 'out')>Kurangi stok (-)</option>
                </select>
            </div>
            <div>
                <x-input-label for="quantity" value="Jumlah" />
                <x-text-input id="quantity" name="quantity" type="number" step="0.001" min="0.001" class="mt-1 block w-full" :value="old('quantity')" required />
            </div>
        </div>

        <div>
            <x-input-label for="note" value="Alasan penyesuaian" />
            <textarea id="note" name="note" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required placeholder="Contoh: barang rusak, susut, atau koreksi input">{{ old('note') }}</textarea>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan Penyesuaian</button>
            <a href="{{ route('admin.inventory.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Batal</a>
        </div>
    </form>
</x-admin-layout>
