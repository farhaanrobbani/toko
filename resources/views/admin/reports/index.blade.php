<x-admin-layout title="Laporan">
    @php
        $summary = $report['summary'];
        $exportQuery = ['start_date' => $from->format('Y-m-d'), 'end_date' => $to->format('Y-m-d')];
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Laporan Penjualan</h2>
            <p class="mt-1 text-sm text-gray-500">Ringkasan penjualan POS dan pesanan online.</p>
        </div>
        <a href="{{ route('admin.reports.export', $exportQuery) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            Unduh CSV
        </a>
    </div>

    <form method="GET" action="{{ route('admin.reports.index') }}" class="mt-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <x-input-label for="start_date" value="Dari Tanggal" class="text-xs" />
                <input id="start_date" name="start_date" type="date" value="{{ $from->format('Y-m-d') }}" class="mt-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <x-input-label for="end_date" value="Sampai Tanggal" class="text-xs" />
                <input id="end_date" name="end_date" type="date" value="{{ $to->format('Y-m-d') }}" class="mt-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Terapkan</button>
            <a href="{{ route('admin.reports.index') }}" class="px-2 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Reset</a>
        </div>
        <p class="mt-3 text-xs text-gray-500">
            Periode {{ $from->format('d/m/Y') }}–{{ $to->format('d/m/Y') }}.
            Hanya transaksi POS selesai dan pesanan online yang sudah dibayar yang dihitung.
        </p>
    </form>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Total Penjualan</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ rupiah($summary['total_collected']) }}</p>
            <p class="mt-1 text-xs text-gray-500">Produk {{ rupiah($summary['revenue']) }} · Ongkir {{ rupiah($summary['shipping']) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Laba Kotor</p>
            <p class="mt-1 text-2xl font-bold {{ $summary['gross_profit'] >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ rupiah($summary['gross_profit']) }}</p>
            <p class="mt-1 text-xs text-gray-500">HPP {{ rupiah($summary['cogs']) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Transaksi</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($summary['transactions'], 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-gray-500">POS {{ $summary['pos_transactions'] }} · Online {{ $summary['online_transactions'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Rata-rata per Transaksi</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ rupiah($summary['average']) }}</p>
            <p class="mt-1 text-xs text-gray-500">Diskon POS {{ rupiah($summary['discount']) }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h3 class="font-semibold text-gray-900">Metode Pembayaran</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Metode</th>
                            <th class="px-5 py-3 text-right">Transaksi</th>
                            <th class="px-5 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($report['payments'] as $payment)
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-900">{{ $payment['label'] }}</td>
                                <td class="px-5 py-3 text-right text-gray-600">{{ number_format($payment['count'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-right font-medium text-gray-900">{{ rupiah($payment['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-8 text-center text-gray-500">Belum ada pembayaran pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h3 class="font-semibold text-gray-900">Produk Terlaris</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Produk</th>
                            <th class="px-5 py-3 text-right">Qty</th>
                            <th class="px-5 py-3 text-right">Penjualan</th>
                            <th class="px-5 py-3 text-right">Laba</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($report['products'] as $product)
                            <tr>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-900">{{ $product['name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $product['sku'] }}</p>
                                </td>
                                <td class="px-5 py-3 text-right text-gray-600">{{ qty($product['quantity']) }}</td>
                                <td class="px-5 py-3 text-right text-gray-900">{{ rupiah($product['revenue']) }}</td>
                                <td class="px-5 py-3 text-right font-medium text-gray-900">{{ rupiah($product['profit']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-gray-500">Belum ada produk terjual pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h3 class="font-semibold text-gray-900">Transaksi Terbaru</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-5 py-3">Jenis</th>
                        <th class="px-5 py-3">Nomor</th>
                        <th class="px-5 py-3">Pelanggan</th>
                        <th class="px-5 py-3">Pembayaran</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($report['recent'] as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-600">{{ $transaction['at']->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $transaction['type'] === 'pos' ? 'bg-indigo-50 text-indigo-700' : 'bg-blue-50 text-blue-700' }}">
                                    {{ $transaction['type'] === 'pos' ? 'POS' : 'Online' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <a
                                    href="{{ $transaction['type'] === 'pos' ? route('pos.receipt', $transaction['id']) : route('admin.orders.show', $transaction['id']) }}"
                                    class="font-medium text-indigo-600 hover:text-indigo-700"
                                >{{ $transaction['number'] }}</a>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $transaction['customer'] }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $transaction['payment'] }}</td>
                            <td class="px-5 py-3 text-right font-medium text-gray-900">{{ rupiah($transaction['total']) }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $transaction['status_class'] }}">{{ $transaction['status'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500">Belum ada transaksi pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
