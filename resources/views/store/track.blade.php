<x-store-layout title="Lacak Pesanan">
    <div class="mx-auto max-w-lg">
        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Lacak Pesanan</h1>
            <p class="mt-1 text-sm text-gray-500">Masukkan nomor pesanan dan nomor telepon yang Anda gunakan saat checkout.</p>

            <form method="POST" action="{{ route('track.lookup') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <x-input-label for="order_number" value="Nomor pesanan" />
                    <x-text-input id="order_number" name="order_number" class="mt-1 block w-full" :value="old('order_number')" placeholder="ORD-20261023-0001" required />
                </div>
                <div>
                    <x-input-label for="customer_phone" value="Nomor telepon" />
                    <x-text-input id="customer_phone" name="customer_phone" class="mt-1 block w-full" :value="old('customer_phone')" placeholder="08xxxxxxxxxx" required />
                </div>
                <button type="submit" class="w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Cari Pesanan</button>
            </form>
        </div>
    </div>
</x-store-layout>
