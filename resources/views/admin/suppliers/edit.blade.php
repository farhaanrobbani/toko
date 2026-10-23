<x-admin-layout title="Edit Supplier">
    <h2 class="mb-4 text-lg font-semibold text-gray-900">Edit {{ $supplier->name }}</h2>
    @include('admin.suppliers._form')
</x-admin-layout>
