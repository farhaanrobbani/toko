<?php

namespace App\Services;

use App\Enums\PosPaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private readonly StockService $stockService) {}

    /**
     * @param  array{warehouse_id: int, payment_method: string, items: array<int, array{product_id: int|string, quantity: float|int|string}>, paid: float|int|string, discount?: float|int|string|null, customer_name?: string|null, customer_phone?: string|null, note?: string|null}  $data
     */
    public function checkout(array $data, User $cashier): Sale
    {
        return DB::transaction(function () use ($data, $cashier) {
            $warehouse = Warehouse::query()
                ->whereKey($data['warehouse_id'])
                ->where('is_active', true)
                ->first();

            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Lokasi stok POS tidak ditemukan atau tidak aktif.',
                ]);
            }

            $quantities = [];

            foreach ($data['items'] as $row) {
                $productId = (int) $row['product_id'];
                $quantities[$productId] = round(($quantities[$productId] ?? 0) + (float) $row['quantity'], 3);
            }

            $products = Product::query()
                ->active()
                ->whereIn('id', array_keys($quantities))
                ->get()
                ->keyBy('id');

            $lines = [];
            $subtotal = 0.0;

            foreach ($quantities as $productId => $quantity) {
                /** @var Product|null $product */
                $product = $products->get($productId);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'Ada produk yang tidak ditemukan atau sudah tidak aktif.',
                    ]);
                }

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Jumlah produk harus lebih dari nol.',
                    ]);
                }

                $lineSubtotal = round((float) $product->sell_price * $quantity, 2);
                $subtotal += $lineSubtotal;

                $lines[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $subtotal = round($subtotal, 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);

            if ($discount < 0 || $discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount' => 'Diskon tidak boleh melebihi subtotal.',
                ]);
            }

            $total = round($subtotal - $discount, 2);
            $paid = round((float) $data['paid'], 2);

            if ($paid + 0.0005 < $total) {
                throw ValidationException::withMessages([
                    'paid' => 'Nominal pembayaran kurang dari total belanja.',
                ]);
            }

            $sale = Sale::query()->create([
                'sale_number' => $this->nextSaleNumber(),
                'status' => SaleStatus::Completed,
                'payment_method' => PosPaymentMethod::from($data['payment_method']),
                'warehouse_id' => $warehouse->getKey(),
                'cashier_id' => $cashier->getKey(),
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid' => $paid,
                'change' => round($paid - $total, 2),
                'note' => $data['note'] ?? null,
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];

                SaleItem::query()->create([
                    'sale_id' => $sale->getKey(),
                    'product_id' => $product->getKey(),
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->unit,
                    'price' => $product->sell_price,
                    'quantity' => $line['quantity'],
                    'subtotal' => $line['subtotal'],
                    'cost_price' => $product->cost_price,
                ]);

                $this->stockService->record(
                    product: $product,
                    warehouse: $warehouse,
                    type: StockMovementType::SalePos,
                    quantity: -1 * $line['quantity'],
                    unitCost: (float) $product->cost_price,
                    reference: $sale,
                    note: "Penjualan POS {$sale->sale_number}",
                    user: $cashier,
                );
            }

            return $sale->refresh()->load('items', 'warehouse', 'cashier');
        });
    }

    public function void(Sale $sale, User $user, string $reason): Sale
    {
        return DB::transaction(function () use ($sale, $user, $reason) {
            $sale = Sale::query()->whereKey($sale->getKey())->lockForUpdate()->firstOrFail();

            if ($sale->status !== SaleStatus::Completed) {
                throw ValidationException::withMessages([
                    'status' => 'Transaksi ini sudah dibatalkan.',
                ]);
            }

            $warehouse = Warehouse::query()->findOrFail($sale->warehouse_id);
            $sale->load('items');

            foreach ($sale->items as $item) {
                if ($item->product) {
                    $this->stockService->record(
                        product: $item->product,
                        warehouse: $warehouse,
                        type: StockMovementType::Return,
                        quantity: (float) $item->quantity,
                        unitCost: (float) $item->cost_price,
                        reference: $sale,
                        note: "Void transaksi {$sale->sale_number}",
                        user: $user,
                    );
                }
            }

            $sale->update([
                'status' => SaleStatus::Void,
                'voided_by' => $user->getKey(),
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            return $sale->refresh()->load('items', 'warehouse', 'cashier', 'voidedBy');
        });
    }

    private function nextSaleNumber(): string
    {
        $prefix = 'POS-'.now()->format('Ymd').'-';
        $count = Sale::query()->where('sale_number', 'like', $prefix.'%')->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
