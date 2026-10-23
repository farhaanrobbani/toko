@php
    $editing = isset($warehouse);
    $isDefault = old('is_default', $warehouse->is_default ?? ($isDefault ?? false));
    $isActive = old('is_active', $warehouse->is_active ?? true);
@endphp

<form method="POST" action="{{ $editing ? route('admin.warehouses.update', $warehouse) : route('admin.warehouses.store') }}" class="space-y-5">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-input-label for="name" value="Nama Lokasi" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $warehouse->name ?? '')" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="code" value="Kode" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $warehouse->code ?? '')" placeholder="gudang" required />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="address" value="Alamat (opsional)" />
            <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $warehouse->address ?? '')" />
            <x-input-error :messages="$errors->get('address')" class="mt-2" />
        </div>
    </div>

    <div class="space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <input type="hidden" name="is_default" value="0">
        <label class="flex items-center gap-3">
            <input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked((bool) $isDefault)>
            <span class="text-sm font-medium text-gray-700">Jadikan lokasi default</span>
        </label>

        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-3">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked((bool) $isActive)>
            <span class="text-sm font-medium text-gray-700">Lokasi aktif</span>
        </label>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.warehouses.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
        <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Tambah Lokasi' }}</x-primary-button>
    </div>
</form>
