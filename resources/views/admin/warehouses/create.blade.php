<x-admin-layout title="Tambah Lokasi">
    <div class="max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @include('admin.warehouses._form', ['isDefault' => $isDefault])
    </div>
</x-admin-layout>
