<x-admin-layout title="Stok Opname">
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div>
                <h2 class="font-semibold text-gray-900">Sesi Stok Opname</h2>
                <p class="text-sm text-gray-500">Hitung fisik barang dan sesuaikan selisih stok sistem.</p>
            </div>
            <a href="{{ route('admin.stock-counts.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Opname Baru</a>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-gray-200 bg-gray-50 px-5 py-3">
            <div>
                <x-input-label for="status" value="Status" class="text-xs" />
                <select id="status" name="status" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua status</option>
                    @foreach (\App\Enums\StockCountStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Filter</button>
            @if (request()->filled('status'))
                <a href="{{ route('admin.stock-counts.index') }}" class="px-2 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Referensi</th>
                        <th class="px-5 py-3">Lokasi</th>
                        <th class="px-5 py-3">Dibuat</th>
                        <th class="px-5 py-3 text-right">Item</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($counts as $count)
                        <tr>
                            <td class="px-5 py-3 font-mono text-xs text-gray-700">{{ $count->reference_number }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $count->warehouse?->name }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $count->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ number_format($count->items_count, 0, ',', '.') }}</td>
                            <td class="px-5 py-3">
                                @php
                                    $badge = match ($count->status->value) {
                                        'draft' => 'bg-gray-100 text-gray-700',
                                        'completed' => 'bg-green-50 text-green-700',
                                        'cancelled' => 'bg-red-50 text-red-700',
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ $count->status->label() }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.stock-counts.show', $count) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">Belum ada sesi opname.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($counts->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">
                {{ $counts->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
