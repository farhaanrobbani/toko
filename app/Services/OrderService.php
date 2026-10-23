<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly StockService $stockService,
    ) {}

    /**
     * @param  array{customer_name: string, customer_phone: string, customer_address?: string|null, customer_note?: string|null, payment_method: string, delivery_method: string}  $data
     */
    public function place(array $data): Order
    {
        $lines = $this->cartService->lines();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Keranjang masih kosong.',
            ]);
        }

        if ($lines->contains('insufficient', true)) {
            throw ValidationException::withMessages([
                'cart' => 'Ada produk yang stoknya tidak mencukupi. Perbarui keranjang terlebih dahulu.',
            ]);
        }

        $warehouse = $this->cartService->warehouse();

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'cart' => 'Belum ada lokasi stok aktif untuk pesanan online.',
            ]);
        }

        $order = DB::transaction(function () use ($data, $lines, $warehouse) {
            $subtotal = round($lines->sum('subtotal'), 2);

            $order = Order::query()->create([
                'order_number' => $this->nextOrderNumber(),
                'tracking_token' => Str::random(48),
                'status' => OrderStatus::PendingPayment,
                'payment_method' => $data['payment_method'],
                'delivery_method' => $data['delivery_method'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_address' => $data['customer_address'] ?? null,
                'customer_note' => $data['customer_note'] ?? null,
                'warehouse_id' => $warehouse->getKey(),
                'subtotal' => $subtotal,
                'shipping_cost' => 0,
                'total' => $subtotal,
                'expires_at' => now()->addHours(max(1, (int) Setting::get('order_expiry_hours', 24))),
            ]);

            foreach ($lines->sortBy(fn (array $line) => $line['product']->getKey()) as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $quantity = (float) $line['quantity'];

                OrderItem::query()->create([
                    'order_id' => $order->getKey(),
                    'product_id' => $product->getKey(),
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->unit,
                    'price' => $product->sell_price,
                    'quantity' => $quantity,
                    'subtotal' => $line['subtotal'],
                    'cost_price' => $product->cost_price,
                ]);

                $this->stockService->reserve($product, $warehouse, $quantity);
            }

            return $order;
        });

        $this->cartService->clear();

        return $order->load('items', 'warehouse');
    }

    public function confirmPayment(Order $order, User $user): Order
    {
        return DB::transaction(function () use ($order, $user) {
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::PendingPayment || $order->stock_committed_at !== null) {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan ini tidak bisa dikonfirmasi lagi.',
                ]);
            }

            $warehouse = Warehouse::query()->findOrFail($order->warehouse_id);
            $order->load('items');

            foreach ($order->items as $item) {
                if (! $item->product) {
                    throw ValidationException::withMessages([
                        'items' => "Produk {$item->product_name} sudah tidak tersedia.",
                    ]);
                }

                $this->stockService->commitReserved(
                    product: $item->product,
                    warehouse: $warehouse,
                    quantity: (float) $item->quantity,
                    unitCost: (float) $item->cost_price,
                    reference: $order,
                    note: "Pesanan {$order->order_number}",
                    user: $user,
                );
            }

            $order->update([
                'status' => OrderStatus::Paid,
                'confirmed_by' => $user->getKey(),
                'confirmed_at' => now(),
                'stock_committed_at' => now(),
            ]);

            return $order->refresh()->load('items', 'warehouse');
        });
    }

    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (! $order->isCancellable()) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan yang menunggu pembayaran yang bisa dibatalkan.',
                ]);
            }

            $warehouse = Warehouse::query()->findOrFail($order->warehouse_id);
            $order->load('items');

            foreach ($order->items as $item) {
                if ($item->product) {
                    $this->stockService->release($item->product, $warehouse, (float) $item->quantity);
                }
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $order->refresh()->load('items', 'warehouse');
        });
    }

    public function updateShipping(Order $order, float $shippingCost): Order
    {
        if ($shippingCost < 0) {
            throw ValidationException::withMessages([
                'shipping_cost' => 'Ongkos kirim tidak boleh negatif.',
            ]);
        }

        return DB::transaction(function () use ($order, $shippingCost) {
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::PendingPayment) {
                throw ValidationException::withMessages([
                    'status' => 'Ongkir hanya bisa diubah sebelum pembayaran dikonfirmasi.',
                ]);
            }

            $order->update([
                'shipping_cost' => round($shippingCost, 2),
                'total' => round((float) $order->subtotal + $shippingCost, 2),
            ]);

            return $order->refresh();
        });
    }

    public function advance(Order $order, OrderStatus $next): Order
    {
        return DB::transaction(function () use ($order, $next) {
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (! $order->status->canAdvanceTo($next)) {
                throw ValidationException::withMessages([
                    'status' => "Status tidak bisa diubah dari {$order->status->label()} ke {$next->label()}.",
                ]);
            }

            $order->update(['status' => $next]);

            return $order->refresh();
        });
    }

    public function uploadProof(Order $order, UploadedFile $file): Order
    {
        return DB::transaction(function () use ($order, $file) {
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::PendingPayment) {
                throw ValidationException::withMessages([
                    'proof' => 'Bukti pembayaran hanya bisa diunggah untuk pesanan yang menunggu pembayaran.',
                ]);
            }

            if ($order->payment_proof_path) {
                Storage::disk('public')->delete($order->payment_proof_path);
            }

            $order->update([
                'payment_proof_path' => $file->store('payment-proofs', 'public'),
                'payment_uploaded_at' => now(),
            ]);

            return $order->refresh();
        });
    }

    public function expirePending(): int
    {
        $expired = 0;

        Order::query()
            ->where('status', OrderStatus::PendingPayment)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->each(function (Order $order) use (&$expired): void {
                try {
                    $this->cancel($order);
                    $expired++;
                } catch (ValidationException) {
                    // The order changed between selection and cancellation.
                }
            });

        return $expired;
    }

    private function nextOrderNumber(): string
    {
        $prefix = 'ORD-'.now()->format('Ymd').'-';
        $count = Order::query()->where('order_number', 'like', $prefix.'%')->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
