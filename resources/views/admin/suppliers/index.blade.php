<x-admin-layout title="Supplier">
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div>
                <h2 class="font-semibold text-gray-900">Daftar Supplier</h2>
                <p class="text-sm text-gray-500">Catat pemasok untuk mempermudah pembelian stok.</p>
            </div>
            <a href="{{ route('admin.suppliers.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tambah Supplier</a>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-gray-200 bg-gray-50 px-5 py-3">
            <div class="min-w-56 flex-1">
                <x-input-label for="q" value="Cari" class="text-xs" />
                <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="request('q')" placeholder="Nama atau telepon" />
            </div>
            <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cari</button>
            @if (request()->filled('q'))
                <a href="{{ route('admin.suppliers.index') }}" class="px-2 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">Telepon</th>
                        <th class="px-5 py-3">Alamat</th>
                        <th class="px-5 py-3 text-right">Pembelian</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">{{ $supplier->name }}</p>
                                @if ($supplier->notes)
                                    <p class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($supplier->notes, 60) }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ $supplier->phone ?: '—' }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $supplier->address ?: '—' }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ number_format($supplier->purchases_count, 0, ',', '.') }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Edit</a>
                                    <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Hapus supplier ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">Belum ada supplier.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($suppliers->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
