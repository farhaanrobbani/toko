@php
    $editing = isset($product);
    $cost = (float) old('cost_price', $product->cost_price ?? 0);
    $sell = (float) old('sell_price', $product->sell_price ?? 0);
    $margin = old('margin_override', $product->margin_override ?? null);
    $minimumStock = old('min_stock', $product->min_stock ?? (int) \App\Models\Setting::get('default_min_stock', 5));
    $isActive = old('is_active', $product->is_active ?? true);
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}"
    enctype="multipart/form-data"
    class="space-y-6"
    x-data="{
        cost: {{ $cost }},
        sell: {{ $sell }},
        margin: {{ $margin === null || $margin === '' ? 'null' : (float) $margin }},
        defaultMargin: {{ $defaultMargin }},
        rounding: {{ $priceRounding }},
        get effectiveMargin() {
            return this.margin === null || this.margin === '' ? Number(this.defaultMargin) : Number(this.margin);
        },
        get recommendedPrice() {
            const base = Number(this.cost) * (1 + (this.effectiveMargin / 100));
            if (base <= 0) return 0;
            return Math.ceil(base / this.rounding) * this.rounding;
        },
        get currentMargin() {
            if (Number(this.cost) <= 0) return null;
            return ((Number(this.sell) - Number(this.cost)) / Number(this.cost)) * 100;
        },
        formatRupiah(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(value || 0));
        },
        applyRecommended() {
            this.sell = this.recommendedPrice;
        }
    }"
>
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 font-semibold text-gray-900">Informasi Produk</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="name" value="Nama Produk" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $product->name ?? '')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="sku" value="SKU" />
                        <x-text-input id="sku" name="sku" type="text" class="mt-1 block w-full" :value="old('sku', $product->sku ?? '')" required />
                        <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="barcode" value="Barcode (opsional)" />
                        <x-text-input id="barcode" name="barcode" type="text" class="mt-1 block w-full" :value="old('barcode', $product->barcode ?? '')" />
                        <x-input-error :messages="$errors->get('barcode')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="category_id" value="Kategori" />
                        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tanpa kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="unit" value="Satuan" />
                        <x-text-input id="unit" name="unit" type="text" class="mt-1 block w-full" :value="old('unit', $product->unit ?? 'pcs')" required />
                        <x-input-error :messages="$errors->get('unit')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="description" value="Deskripsi (opsional)" />
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $product->description ?? '') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 font-semibold text-gray-900">Harga & Stok</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="cost_price" value="Harga Modal (HPP)" />
                        <x-text-input id="cost_price" name="cost_price" type="number" step="0.01" min="0" class="mt-1 block w-full" x-model.number="cost" required />
                        <x-input-error :messages="$errors->get('cost_price')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="sell_price" value="Harga Jual" />
                        <x-text-input id="sell_price" name="sell_price" type="number" step="0.01" min="0" class="mt-1 block w-full" x-model.number="sell" required />
                        <x-input-error :messages="$errors->get('sell_price')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="margin_override" value="Margin Khusus (%) — opsional" />
                        <x-text-input id="margin_override" name="margin_override" type="number" step="0.01" min="0" max="1000" class="mt-1 block w-full" x-model="margin" placeholder="{{ $defaultMargin }}" />
                        <p class="mt-1 text-xs text-gray-500">Kosongkan untuk memakai margin default {{ number_format($defaultMargin, 0, ',', '.') }}%.</p>
                        <x-input-error :messages="$errors->get('margin_override')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="min_stock" value="Stok Minimum" />
                        <x-text-input id="min_stock" name="min_stock" type="number" min="0" class="mt-1 block w-full" :value="$minimumStock" required />
                        <x-input-error :messages="$errors->get('min_stock')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-5 rounded-lg border border-indigo-100 bg-indigo-50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-indigo-600">Rekomendasi Harga Jual</p>
                            <p class="mt-1 text-2xl font-semibold text-indigo-900" x-text="formatRupiah(recommendedPrice)"></p>
                            <p class="mt-1 text-xs text-indigo-700">
                                Margin efektif <span x-text="Number(effectiveMargin).toFixed(1)"></span>% ·
                                Margin saat ini
                                <span x-text="currentMargin === null ? '—' : Number(currentMargin).toFixed(1) + '%'"></span>
                            </p>
                        </div>
                        <button type="button" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" @click="applyRecommended()">
                            Pakai Harga Ini
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 font-semibold text-gray-900">Foto Produk</h2>

                @if ($editing && $product->image_path)
                    <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="mb-3 h-40 w-full rounded-lg object-cover">
                @else
                    <div class="mb-3 flex h-40 w-full items-center justify-center rounded-lg bg-gray-100 text-sm text-gray-400">Belum ada foto</div>
                @endif

                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
                <p class="mt-2 text-xs text-gray-500">JPG, PNG, atau WEBP. Maksimal 2MB.</p>
                <x-input-error :messages="$errors->get('image')" class="mt-2" />
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked((bool) $isActive)>
                    <span class="text-sm font-medium text-gray-700">Produk aktif</span>
                </label>
                <p class="mt-2 text-xs text-gray-500">Produk nonaktif tidak tampil di katalog online.</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3">
                    <x-primary-button class="justify-center">{{ $editing ? 'Simpan Perubahan' : 'Tambah Produk' }}</x-primary-button>
                    <a href="{{ route('admin.products.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
                </div>
            </div>
        </div>
    </div>
</form>
