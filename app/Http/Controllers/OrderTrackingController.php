<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function form(): View
    {
        return view('store.track');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:50'],
            'customer_phone' => ['required', 'string', 'max:30'],
        ]);

        $order = Order::query()
            ->where('order_number', $data['order_number'])
            ->where('customer_phone', $data['customer_phone'])
            ->first();

        if (! $order) {
            return back()
                ->withInput()
                ->withErrors(['order_number' => 'Pesanan tidak ditemukan. Periksa nomor pesanan dan nomor telepon.']);
        }

        return redirect()->route('orders.show', $order->tracking_token);
    }

    public function show(Order $order): View
    {
        return view('store.order', [
            'order' => $order->load('items', 'warehouse'),
        ]);
    }

    public function uploadProof(Request $request, Order $order, OrderService $orderService): RedirectResponse
    {
        $request->validate([
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'proof.required' => 'Pilih file bukti pembayaran.',
            'proof.image' => 'Bukti pembayaran harus berupa gambar.',
        ]);

        $orderService->uploadProof($order, $request->file('proof'));

        return back()->with('status', 'Bukti pembayaran diterima. Admin akan memverifikasi pesanan Anda.');
    }
}
