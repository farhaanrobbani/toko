@php
    $isDraft = $count->status === \App\Enums\StockCountStatus::Draft;
    $differenceCount = $count->items->filter(fn ($item) => $item->difference !== null && abs((float) $item->difference) > 0.0005)->count();
    $allCounted = $count->items->every(fn ($item) => $item->counted_qty !== null);
@endphp

<x-admin-layout :title="'Opname '.$count->reference_number">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.stock-counts.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">&larr; Kembali ke opname</a>
            <h2 class="mt-2 text-2xl font-semibold text-gray-900">{{ $count->reference_number }}</h2>
            <p class="text-sm text-gray-500">{{ $count->warehouse?->name }} · {{ $count->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($isDraft)
                <form method="POST" action="{{ route('admin.stock-counts.complete', $count) }}" onsubmit="return confirm('Selesaikan opname? Selisih akan mengubah stok sistem.')">
                    @csrf
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" @disabled(! $allCounted)>Selesaikan Opname</button>
                </form>
                <form method="POST" action="{{ route('admin.stock-counts.cancel', $count) }}" onsubmit="return confirm('Batalkan sesi opname ini?')">
                    @csrf
                    <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Batalkan</button>
                </form>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Status</p>
            <p class="mt-1 font-medium text-gray-900">{{ $count->status->label() }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Jumlah Item</p>
            <p class="mt-1 font-medium text-gray-900">{{ number_format($count->items->count(), 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Item Berselisih</p>
            <p class="mt-1 font-medium {{ $differenceCount > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ number_format($differenceCount, 0, ',', '.') }}</p>
        </div>
    </div>

    @if ($count->note)
        <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Catatan</p>
            <p class="mt-1">{{ $count->note }}</p>
        </div>
    @endif

    @if ($isDraft && ! $allCounted)
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Isi jumlah fisik semua item lalu simpan sebelum menyelesaikan opname.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.stock-counts.update', $count) }}" class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        @csrf
        @method('PUT')

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div>
                <h3 class="font-semibold text-gray-900">Hitungan Fisik</h3>
                <p class="text-sm text-gray-500">Selisih = jumlah fisik - stok sistem.</p>
            </div>
            @if ($isDraft)
                <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Simpan Hitungan</button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Produk</th>
                        <th class="px-5 py-3 text-right">Stok Sistem</th>
                        <th class="w-40 px-5 py-3">Jumlah Fisik</th>
                        <th class="px-5 py-3 text-right">Selisih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($count->items as $item)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                                <p class="text-xs text-gray-500">{{ $item->sku }}</p>
                            </td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ qty($item->system_qty) }}</td>
                            <td class="px-5 py-3">
                                @if ($isDraft)
                                    <input
                                        name="counted[{{ $item->id }}]"
                                        type="number"
                                        step="0.001"
                                        min="0"
                                        value="{{ old('counted.'.$item->id, $item->counted_qty) }}"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="{{ qty($item->system_qty) }}"
                                    >
                                @else
                                    <span class="text-gray-900">{{ $item->counted_qty !== null ? qty($item->counted_qty) : '—' }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right font-medium {{ $item->difference !== null && (float) $item->difference < 0 ? 'text-red-600' : 'text-green-700' }}">
                                @if ($item->difference === null)
                                    —
                                @else
                                    {{ (float) $item->difference > 0 ? '+' : '' }}{{ qty($item->difference) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($isDraft)
            <div class="border-t border-gray-200 bg-gray-50 px-5 py-3 text-right">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan Hitungan</button>
            </div>
        @endif
    </form>
</x-admin-layout>
