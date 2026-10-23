@props(['title' => null, 'noindex' => false])

@php
    $storeName = \App\Models\Setting::get('store_name', 'Toko');
    $cartService = app(\App\Services\CartService::class);
    $cartCount = $cartService->count();
    $stockWarehouse = $cartService->warehouse();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="stock-warehouse-id" content="{{ $stockWarehouse?->getKey() }}">
        <meta name="stock-endpoint" content="{{ route('stock.live') }}">
        @if ($noindex)
            <meta name="robots" content="noindex, nofollow">
        @endif

        <title>{{ $title ? $title.' — '.$storeName : $storeName }}</title>
        <meta name="description" content="Belanja kebutuhan harian dari {{ $storeName }}. Pesan online, bayar QRIS atau COD.">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <style>[x-cloak] { display: none !important; }</style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white">{{ mb_substr($storeName, 0, 1) }}</span>
                    <span class="text-lg font-semibold tracking-tight text-gray-900">{{ $storeName }}</span>
                </a>

                <nav class="hidden items-center gap-6 text-sm font-medium text-gray-600 sm:flex">
                    <a href="{{ route('home') }}" class="hover:text-indigo-600">Belanja</a>
                    <a href="{{ route('track.form') }}" class="hover:text-indigo-600">Lacak Pesanan</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="hover:text-indigo-600">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-indigo-600">Masuk Admin</a>
                    @endauth
                </nav>

                <div class="flex items-center gap-2">
                    <a href="{{ route('track.form') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 sm:hidden">Lacak</a>
                    <a href="{{ route('cart.index') }}" class="relative inline-flex items-center gap-2 rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        Keranjang
                        @if ($cartCount > 0)
                            <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-white px-1.5 py-0.5 text-xs font-bold text-gray-900">{{ qty($cartCount) }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            @if (session('status'))
                <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-medium">Periksa kembali input berikut:</p>
                    <ul class="mt-1 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </main>

        <footer class="mt-12 border-t border-gray-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-8 text-sm text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <p class="font-semibold text-gray-900">{{ $storeName }}</p>
                    <p>{{ \App\Models\Setting::get('store_address') ?: 'Belanja online, bayar QRIS atau COD.' }}</p>
                </div>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('track.form') }}" class="hover:text-indigo-600">Lacak Pesanan</a>
                    @if (\App\Models\Setting::get('store_phone'))
                        <a href="tel:{{ \App\Models\Setting::get('store_phone') }}" class="hover:text-indigo-600">{{ \App\Models\Setting::get('store_phone') }}</a>
                    @endif
                    <a href="{{ route('login') }}" class="hover:text-indigo-600">Admin</a>
                </div>
            </div>
        </footer>
    </body>
</html>
