@props(['title' => 'Admin'])

@php
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'pattern' => 'admin.dashboard'],
        ['label' => 'Produk', 'route' => 'admin.products.index', 'pattern' => 'admin.products.*'],
        ['label' => 'Kategori', 'route' => 'admin.categories.index', 'pattern' => 'admin.categories.*'],
        ['label' => 'Lokasi Stok', 'route' => 'admin.warehouses.index', 'pattern' => 'admin.warehouses.*'],
        ['label' => 'Pengaturan', 'route' => 'admin.settings.edit', 'pattern' => 'admin.settings.*'],
        ['label' => 'POS', 'route' => 'pos.index', 'pattern' => 'pos.*'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }} — {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <style>[x-cloak] { display: none !important; }</style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
            <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden" @click="sidebarOpen = false"></div>

            <aside
                x-cloak
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-40 w-64 transform bg-gray-900 text-gray-300 transition-transform duration-200 lg:static lg:translate-x-0"
            >
                <div class="flex h-16 items-center gap-2 border-b border-gray-800 px-5">
                    <span class="text-lg font-semibold text-white">{{ config('app.name') }}</span>
                    <span class="rounded bg-indigo-600 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">Admin</span>
                </div>

                <nav class="space-y-1 px-3 py-4">
                    @foreach ($navigation as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            class="flex items-center rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs($item['pattern']) ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 hover:text-white' }}"
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <button type="button" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 lg:hidden" @click="sidebarOpen = true" aria-label="Buka menu">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <h1 class="text-lg font-semibold text-gray-900">{{ $title }}</h1>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500">{{ auth()->user()->role->label() }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Keluar
                            </button>
                        </form>
                    </div>
                </header>

                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    @if (session('status'))
                        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
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
            </div>
        </div>
    </body>
</html>
