@props(['title' => 'POS'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }} — {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-100 font-sans antialiased">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex h-16 max-w-[1600px] items-center justify-between gap-4 px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <a href="{{ route('pos.index') }}" class="flex items-center gap-2">
                        <span class="text-lg font-semibold text-gray-900">{{ config('app.name') }}</span>
                        <span class="rounded bg-indigo-600 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">POS</span>
                    </a>
                    <nav class="hidden items-center gap-1 sm:flex">
                        <a href="{{ route('pos.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('pos.index') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">Kasir</a>
                        @if (auth()->user()->isOwner())
                            <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Admin</a>
                        @endif
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('profile.edit') }}" class="hidden text-right sm:block">
                        <span class="block text-sm font-medium text-gray-900">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-gray-500">{{ auth()->user()->role->label() }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1600px] p-4 sm:p-6">
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
    </body>
</html>
