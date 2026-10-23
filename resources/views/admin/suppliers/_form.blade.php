@php
    $isEdit = $supplier->exists;
    $action = $isEdit ? route('admin.suppliers.update', $supplier) : route('admin.suppliers.store');
@endphp

<form method="POST" action="{{ $action }}" class="max-w-2xl space-y-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div>
        <x-input-label for="name" value="Nama supplier" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $supplier->name)" required autofocus />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="phone" value="Telepon" />
            <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $supplier->phone)" />
        </div>
        <div>
            <x-input-label for="address" value="Alamat" />
            <x-text-input id="address" name="address" class="mt-1 block w-full" :value="old('address', $supplier->address)" />
        </div>
    </div>

    <div>
        <x-input-label for="notes" value="Catatan" />
        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $supplier->notes) }}</textarea>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Supplier' }}
        </button>
        <a href="{{ route('admin.suppliers.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Batal</a>
    </div>
</form>
