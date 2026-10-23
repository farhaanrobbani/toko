<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockLevel;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(private readonly StockService $stockService) {}

    /**
     * @param  array{supplier_id?: int|null, warehouse_id: int, invoice_number?: string|null, purchased_at: string, note?: string|null, items: array<int, array{product_id: int, quantity: numeric-string|float|int, unit_cost: numeric-string|float|int}>}  $data
     */
    public function create(array $data, User $user): Purchase
    {
        if (empty($data['items'])) {
            throw ValidationException::withMessages([
                'items' => 'Minimal satu produk harus diisi.',
            ]);
        }

        return DB::transaction(function () use ($data, $user) {
            $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);

            $purchase = Purchase::query()->create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'warehouse_id' => $warehouse->getKey(),
                'invoice_number' => $data['invoice_number'] ?? null,
                'purchased_at' => $data['purchased_at'],
                'total' => 0,
                'note' => $data['note'] ?? null,
                'user_id' => $user->getKey(),
            ]);

            $total = 0.0;

            foreach ($data['items'] as $item) {
                $quantity = round((float) $item['quantity'], 3);
                $unitCost = round((float) $item['unit_cost'], 2);

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Jumlah barang harus lebih dari nol.',
                    ]);
                }

                if ($unitCost < 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Harga beli tidak boleh negatif.',
                    ]);
                }

                $product = Product::query()->whereKey($item['product_id'])->lockForUpdate()->firstOrFail();

                $subtotal = round($quantity * $unitCost, 2);
                $total += $subtotal;

                PurchaseItem::query()->create([
                    'purchase_id' => $purchase->getKey(),
                    'product_id' => $product->getKey(),
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                ]);

                $this->updateMovingAverage($product, $quantity, $unitCost);

                $this->stockService->record(
                    product: $product,
                    warehouse: $warehouse,
                    type: StockMovementType::Purchase,
                    quantity: $quantity,
                    unitCost: $unitCost,
                    reference: $purchase,
                    note: $data['note'] ?? null,
                    user: $user,
                );
            }

            $purchase->update(['total' => round($total, 2)]);

            return $purchase->load('items');
        });
    }

    private function updateMovingAverage(Product $product, float $incomingQty, float $incomingCost): void
    {
        $currentQty = (float) StockLevel::query()
            ->where('product_id', $product->getKey())
            ->sum('qty');

        $currentCost = (float) $product->cost_price;
        $newQty = $currentQty + $incomingQty;

        $newCost = $currentQty > 0 && $newQty > 0
            ? (($currentQty * $currentCost) + ($incomingQty * $incomingCost)) / $newQty
            : $incomingCost;

        $product->update(['cost_price' => round($newCost, 2)]);
    }
}
