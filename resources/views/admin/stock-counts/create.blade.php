<x-admin-layout title="Opname Baru">
    <form method="POST" action="{{ route('admin.stock-counts.store') }}" class="max-w-2xl space-y-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf

        <div>
            <h2 class="font-semibold text-gray-900">Mulai Sesi Opname</h2>
            <p class="text-sm text-gray-500">Sistem akan mengambil snapshot stok saat ini untuk lokasi yang dipilih.</p>
        </div>

        <div>
            <x-input-label for="warehouse_id" value="Lokasi" />
            <select id="warehouse_id" name="warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $warehouse->is_default ? $warehouse->id : '') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label for="note" value="Catatan" />
            <textarea id="note" name="note" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: opname akhir bulan">{{ old('note') }}</textarea>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Mulai Opname</button>
            <a href="{{ route('admin.stock-counts.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Batal</a>
        </div>
    </form>
</x-admin-layout>
