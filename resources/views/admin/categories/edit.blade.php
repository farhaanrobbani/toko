<x-admin-layout title="Edit Kategori">
    <div class="max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @include('admin.categories._form', ['category' => $category])
    </div>
</x-admin-layout>
