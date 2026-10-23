<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(CartService $cartService): View|RedirectResponse
    {
        $lines = $cartService->lines();

        if ($lines->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Keranjang masih kosong.']);
        }

        if ($lines->contains('insufficient', true)) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Ada produk yang stoknya tidak mencukupi.']);
        }

        return view('store.checkout', [
            'lines' => $lines,
            'subtotal' => $cartService->subtotal(),
            'warehouse' => $cartService->warehouse(),
        ]);
    }

    public function store(Request $request, OrderService $orderService): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'customer_address' => ['nullable', 'required_if:delivery_method,delivery', 'string', 'max:1000'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'delivery_method' => ['required', Rule::enum(DeliveryMethod::class)],
            'agree' => ['accepted'],
        ], [
            'agree.accepted' => 'Anda harus menyetujui ketentuan pemesanan.',
            'customer_address.required_if' => 'Alamat pengiriman wajib diisi untuk pesanan yang diantar.',
        ]);

        $order = $orderService->place($data);

        return redirect()->route('orders.show', $order->tracking_token)
            ->with('status', 'Pesanan berhasil dibuat. Simpan tautan pesanan untuk memantau status.');
    }
}
