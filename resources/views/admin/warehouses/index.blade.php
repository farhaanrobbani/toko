<x-admin-layout title="Lokasi Stok">
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div>
                <h2 class="font-semibold text-gray-900">Gudang & Toko</h2>
                <p class="text-sm text-gray-500">Satu lokasi default dipakai untuk pemenuhan pesanan online.</p>
            </div>
            <a href="{{ route('admin.warehouses.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Tambah Lokasi
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">Kode</th>
                        <th class="px-5 py-3">Alamat</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($warehouses as $warehouse)
                        <tr>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-gray-900">{{ $warehouse->name }}</p>
                                    @if ($warehouse->is_default)
                                        <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-indigo-700">Default</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $warehouse->code }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $warehouse->address ?: '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $warehouse->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $warehouse->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.warehouses.edit', $warehouse) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Edit</a>
                                    <form method="POST" action="{{ route('admin.warehouses.destroy', $warehouse) }}" onsubmit="return confirm('Hapus lokasi ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50" @disabled($warehouse->is_default)>Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">Belum ada lokasi stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($warehouses->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">
                {{ $warehouses->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
