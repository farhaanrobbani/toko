<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(CartService $cartService): View
    {
        return view('store.cart', [
            'lines' => $cartService->lines(),
            'subtotal' => $cartService->subtotal(),
            'warehouse' => $cartService->warehouse(),
        ]);
    }

    public function store(Request $request, Product $product, CartService $cartService): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $cartService->add($product, (float) $data['quantity']);

        return back()->with('status', "{$product->name} ditambahkan ke keranjang.");
    }

    public function update(Request $request, Product $product, CartService $cartService): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $quantity = (float) $data['quantity'];

        if ($quantity <= 0) {
            $cartService->remove($product);

            return redirect()->route('cart.index')->with('status', "{$product->name} dihapus dari keranjang.");
        }

        $cartService->update($product, $quantity);

        return redirect()->route('cart.index')->with('status', 'Jumlah produk diperbarui.');
    }

    public function destroy(Product $product, CartService $cartService): RedirectResponse
    {
        $cartService->remove($product);

        return redirect()->route('cart.index')->with('status', "{$product->name} dihapus dari keranjang.");
    }
}
