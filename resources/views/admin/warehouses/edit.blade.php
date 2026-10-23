<x-admin-layout title="Edit Lokasi">
    <div class="max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @include('admin.warehouses._form', ['warehouse' => $warehouse])
    </div>
</x-admin-layout>
