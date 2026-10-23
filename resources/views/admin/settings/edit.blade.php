<x-admin-layout title="Pengaturan">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-4xl space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-semibold text-gray-900">Informasi Toko</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="store_name" value="Nama Toko" />
                    <x-text-input id="store_name" name="store_name" type="text" class="mt-1 block w-full" :value="old('store_name', $settings['store_name'])" required />
                    <x-input-error :messages="$errors->get('store_name')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="store_address" value="Alamat Toko" />
                    <x-text-input id="store_address" name="store_address" type="text" class="mt-1 block w-full" :value="old('store_address', $settings['store_address'])" />
                    <x-input-error :messages="$errors->get('store_address')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="store_phone" value="Nomor Telepon" />
                    <x-text-input id="store_phone" name="store_phone" type="text" class="mt-1 block w-full" :value="old('store_phone', $settings['store_phone'])" placeholder="08xxxxxxxxxx" />
                    <x-input-error :messages="$errors->get('store_phone')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="admin_whatsapp" value="WhatsApp Admin" />
                    <x-text-input id="admin_whatsapp" name="admin_whatsapp" type="text" class="mt-1 block w-full" :value="old('admin_whatsapp', $settings['admin_whatsapp'])" placeholder="628xxxxxxxxxx" />
                    <p class="mt-1 text-xs text-gray-500">Gunakan format internasional tanpa tanda +, mis. 6281234567890.</p>
                    <x-input-error :messages="$errors->get('admin_whatsapp')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-semibold text-gray-900">Harga & Stok</h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label for="default_margin" value="Margin Default (%)" />
                    <x-text-input id="default_margin" name="default_margin" type="number" step="0.01" min="0" max="1000" class="mt-1 block w-full" :value="old('default_margin', $settings['default_margin'])" required />
                    <x-input-error :messages="$errors->get('default_margin')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="price_rounding" value="Pembulatan Harga" />
                    <select id="price_rounding" name="price_rounding" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ([1 => 'Tanpa pembulatan', 100 => 'Ke atas 100', 500 => 'Ke atas 500', 1000 => 'Ke atas 1.000'] as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('price_rounding', $settings['price_rounding']) === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('price_rounding')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="default_min_stock" value="Stok Minimum Default" />
                    <x-text-input id="default_min_stock" name="default_min_stock" type="number" min="0" class="mt-1 block w-full" :value="old('default_min_stock', $settings['default_min_stock'])" required />
                    <x-input-error :messages="$errors->get('default_min_stock')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-semibold text-gray-900">Pemenuhan Pesanan Online</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="online_warehouse_id" value="Lokasi Default Pesanan Online" />
                    <select id="online_warehouse_id" name="online_warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Ikuti lokasi default</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) old('online_warehouse_id', $settings['online_warehouse_id']) === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('online_warehouse_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="order_expiry_hours" value="Kedaluwarsa Pesanan (jam)" />
                    <x-text-input id="order_expiry_hours" name="order_expiry_hours" type="number" min="1" max="720" class="mt-1 block w-full" :value="old('order_expiry_hours', $settings['order_expiry_hours'])" required />
                    <p class="mt-1 text-xs text-gray-500">Pesanan yang belum diproses akan dibatalkan otomatis setelah durasi ini.</p>
                    <x-input-error :messages="$errors->get('order_expiry_hours')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <x-primary-button>Simpan Pengaturan</x-primary-button>
        </div>
    </form>
</x-admin-layout>
