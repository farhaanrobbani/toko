<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function record(
        Product $product,
        Warehouse $warehouse,
        StockMovementType $type,
        float $quantity,
        ?float $unitCost = null,
        ?Model $reference = null,
        ?string $note = null,
        ?User $user = null,
        ?CarbonInterface $occurredAt = null,
    ): StockMovement {
        if (abs($quantity) < 0.0005) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah pergerakan stok tidak boleh nol.',
            ]);
        }

        return DB::transaction(function () use ($product, $warehouse, $type, $quantity, $unitCost, $reference, $note, $user, $occurredAt) {
            $level = StockLevel::query()->firstOrCreate(
                ['product_id' => $product->getKey(), 'warehouse_id' => $warehouse->getKey()],
                ['qty' => 0, 'reserved_qty' => 0],
            );

            $level = StockLevel::query()->whereKey($level->getKey())->lockForUpdate()->firstOrFail();

            $newQty = round((float) $level->qty + $quantity, 3);

            if ($newQty < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok {$product->name} di {$warehouse->name} tidak cukup. Tersedia {$level->availableQty()}.",
                ]);
            }

            $level->update(['qty' => $newQty]);

            return StockMovement::query()->create([
                'product_id' => $product->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'type' => $type,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'note' => $note,
                'user_id' => $user?->getKey(),
                'occurred_at' => $occurredAt ?? now(),
            ]);
        });
    }

    public function stockFor(Product $product, Warehouse $warehouse): float
    {
        $level = StockLevel::query()
            ->where('product_id', $product->getKey())
            ->where('warehouse_id', $warehouse->getKey())
            ->first();

        return $level ? (float) $level->qty : 0.0;
    }

    public function available(Product $product, Warehouse $warehouse): float
    {
        $level = StockLevel::query()
            ->where('product_id', $product->getKey())
            ->where('warehouse_id', $warehouse->getKey())
            ->first();

        return $level ? $level->availableQty() : 0.0;
    }
}
