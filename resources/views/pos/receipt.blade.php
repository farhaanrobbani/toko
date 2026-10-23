@php
    use App\Enums\SaleStatus;

    $storeName = \App\Models\Setting::get('store_name', config('app.name'));
    $storeAddress = \App\Models\Setting::get('store_address');
    $storePhone = \App\Models\Setting::get('store_phone');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Struk {{ $sale->sale_number }} — {{ $storeName }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            @media print {
                .no-print { display: none !important; }
                body { background: #fff !important; }
                .receipt { box-shadow: none !important; border: 0 !important; }
            }
        </style>
    </head>
    <body class="bg-gray-100 font-sans antialiased">
        <div class="mx-auto max-w-xl px-4 py-8">
            @if (session('status'))
                <div class="no-print mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-2">
                <a href="{{ route('pos.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">&larr; Kembali ke POS</a>
                <button type="button" onclick="window.print()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Cetak Struk</button>
            </div>

            <div class="receipt rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="text-center">
                    <h1 class="text-xl font-bold text-gray-900">{{ $storeName }}</h1>
                    @if ($storeAddress)
                        <p class="mt-1 text-xs text-gray-500">{{ $storeAddress }}</p>
                    @endif
                    @if ($storePhone)
                        <p class="text-xs text-gray-500">{{ $storePhone }}</p>
                    @endif
                </div>

                @if ($sale->status === SaleStatus::Void)
                    <div class="mt-4 rounded-lg border-2 border-red-300 bg-red-50 px-4 py-3 text-center">
                        <p class="text-lg font-bold uppercase tracking-wide text-red-700">Transaksi Dibatalkan</p>
                        @if ($sale->void_reason)
                            <p class="mt-1 text-xs text-red-700">{{ $sale->void_reason }}</p>
                        @endif
                    </div>
                @endif

                <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-1 border-y border-dashed border-gray-200 py-3 text-xs">
                    <dt class="text-gray-500">Nomor</dt>
                    <dd class="text-right font-medium text-gray-900">{{ $sale->sale_number }}</dd>
                    <dt class="text-gray-500">Waktu</dt>
                    <dd class="text-right text-gray-700">{{ $sale->created_at->format('d/m/Y H:i') }}</dd>
                    <dt class="text-gray-500">Kasir</dt>
                    <dd class="text-right text-gray-700">{{ $sale->cashier?->name ?? '—' }}</dd>
                    <dt class="text-gray-500">Lokasi</dt>
                    <dd class="text-right text-gray-700">{{ $sale->warehouse?->name ?? '—' }}</dd>
                    @if ($sale->customer_name)
                        <dt class="text-gray-500">Pelanggan</dt>
                        <dd class="text-right text-gray-700">{{ $sale->customer_name }}</dd>
                    @endif
                    <dt class="text-gray-500">Pembayaran</dt>
                    <dd class="text-right text-gray-700">{{ $sale->payment_method->label() }}</dd>
                </dl>

                <table class="mt-4 w-full text-sm">
                    <tbody class="divide-y divide-dashed divide-gray-100">
                        @foreach ($sale->items as $item)
                            <tr>
                                <td class="py-2 align-top">
                                    <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                                    <p class="text-xs text-gray-500">{{ rupiah($item->price) }} × {{ qty($item->quantity) }} {{ $item->unit }}</p>
                                </td>
                                <td class="py-2 text-right align-top font-medium text-gray-900">{{ rupiah($item->subtotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <dl class="mt-4 space-y-1 border-t border-dashed border-gray-200 pt-3 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <dt>Subtotal</dt>
                        <dd>{{ rupiah($sale->subtotal) }}</dd>
                    </div>
                    @if ((float) $sale->discount > 0)
                        <div class="flex justify-between text-gray-600">
                            <dt>Diskon</dt>
                            <dd>-{{ rupiah($sale->discount) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-bold text-gray-900">
                        <dt>Total</dt>
                        <dd>{{ rupiah($sale->total) }}</dd>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <dt>Dibayar</dt>
                        <dd>{{ rupiah($sale->paid) }}</dd>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <dt>Kembalian</dt>
                        <dd>{{ rupiah($sale->change) }}</dd>
                    </div>
                </dl>

                @if ($sale->note)
                    <p class="mt-4 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600">Catatan: {{ $sale->note }}</p>
                @endif

                <p class="mt-6 text-center text-xs text-gray-500">Terima kasih telah berbelanja.</p>

                @if ($sale->voided_at)
                    <p class="mt-3 text-center text-xs text-red-600">
                        Dibatalkan oleh {{ $sale->voidedBy?->name ?? '—' }} pada {{ $sale->voided_at->format('d/m/Y H:i') }}.
                    </p>
                @endif
            </div>

            @if ($sale->status === SaleStatus::Completed && auth()->user()->isOwner())
                <form method="POST" action="{{ route('pos.void', $sale) }}" class="no-print mt-4 rounded-xl border border-red-200 bg-white p-4 shadow-sm" onsubmit="return confirm('Batalkan transaksi dan kembalikan stok?')">
                    @csrf
                    @if ($errors->any())
                        <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <label for="void_reason" class="text-sm font-medium text-red-700">Batalkan transaksi</label>
                    <p class="mt-1 text-xs text-gray-500">Stok akan dikembalikan ke {{ $sale->warehouse?->name }}. Tindakan ini tidak bisa dibatalkan.</p>
                    <div class="mt-3 flex gap-2">
                        <input id="void_reason" name="void_reason" type="text" placeholder="Alasan pembatalan" required maxlength="500" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
                        <button type="submit" class="shrink-0 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Void</button>
                    </div>
                </form>
            @endif
        </div>
    </body>
</html>
