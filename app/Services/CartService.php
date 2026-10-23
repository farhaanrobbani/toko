<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;
use App\Models\StockLevel;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const SESSION_KEY = 'cart';

    private ?Warehouse $resolvedWarehouse = null;

    public function warehouse(): ?Warehouse
    {
        if ($this->resolvedWarehouse) {
            return $this->resolvedWarehouse;
        }

        $onlineWarehouseId = Setting::get('online_warehouse_id');

        return $this->resolvedWarehouse = Warehouse::query()
            ->where('is_active', true)
            ->when($onlineWarehouseId, fn ($query) => $query->orderByRaw('id = ? desc', [(int) $onlineWarehouseId]))
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array<int, float>
     */
    public function raw(): array
    {
        $cart = session()->get(self::SESSION_KEY, []);

        return collect($cart)
            ->mapWithKeys(fn ($quantity, $productId) => [(int) $productId => round((float) $quantity, 3)])
            ->filter(fn (float $quantity) => $quantity > 0)
            ->all();
    }

    public function count(): float
    {
        return array_sum($this->raw());
    }

    /**
     * @return Collection<int, array{product: Product, quantity: float, subtotal: float, available: float, insufficient: bool}>
     */
    public function lines(): Collection
    {
        $cart = $this->raw();

        if ($cart === []) {
            return collect();
        }

        $products = Product::query()
            ->active()
            ->with('category')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        $warehouse = $this->warehouse();

        $levels = $warehouse
            ? StockLevel::query()
                ->where('warehouse_id', $warehouse->getKey())
                ->whereIn('product_id', array_keys($cart))
                ->get()
                ->keyBy('product_id')
            : collect();

        $lines = collect();

        foreach ($cart as $productId => $quantity) {
            $product = $products->get($productId);

            if (! $product) {
                continue;
            }

            $level = $levels->get($productId);
            $available = $level ? max(0, (float) $level->qty - (float) $level->reserved_qty) : 0.0;

            $lines->push([
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => round((float) $product->sell_price * $quantity, 2),
                'available' => $available,
                'insufficient' => $quantity > $available + 0.0005,
            ]);
        }

        return $lines;
    }

    public function subtotal(): float
    {
        return round($this->lines()->sum('subtotal'), 2);
    }

    public function add(Product $product, float $quantity): void
    {
        $quantity = round($quantity, 3);

        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah produk harus lebih dari nol.',
            ]);
        }

        if (! $product->is_active) {
            throw ValidationException::withMessages([
                'quantity' => 'Produk ini sedang tidak dijual.',
            ]);
        }

        $cart = $this->raw();
        $newQuantity = round(($cart[$product->getKey()] ?? 0) + $quantity, 3);

        $this->assertAvailable($product, $newQuantity);

        $cart[$product->getKey()] = $newQuantity;
        session()->put(self::SESSION_KEY, $cart);
    }

    public function update(Product $product, float $quantity): void
    {
        $cart = $this->raw();

        if ($quantity <= 0) {
            unset($cart[$product->getKey()]);
            session()->put(self::SESSION_KEY, $cart);

            return;
        }

        $quantity = round($quantity, 3);

        $this->assertAvailable($product, $quantity);

        $cart[$product->getKey()] = $quantity;
        session()->put(self::SESSION_KEY, $cart);
    }

    public function remove(Product $product): void
    {
        $cart = $this->raw();
        unset($cart[$product->getKey()]);
        session()->put(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function availableFor(Product $product): float
    {
        $warehouse = $this->warehouse();

        if (! $warehouse) {
            return 0.0;
        }

        $level = StockLevel::query()
            ->where('product_id', $product->getKey())
            ->where('warehouse_id', $warehouse->getKey())
            ->first();

        return $level ? $level->availableQty() : 0.0;
    }

    public function assertAvailable(Product $product, float $quantity): void
    {
        $warehouse = $this->warehouse();

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'quantity' => 'Belum ada lokasi stok aktif untuk pesanan online.',
            ]);
        }

        $available = $this->availableFor($product);

        if ($quantity > $available + 0.0005) {
            throw ValidationException::withMessages([
                'quantity' => "Stok {$product->name} hanya tersedia ".qty($available).' '.$product->unit.'.',
            ]);
        }
    }
}
