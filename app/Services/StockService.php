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

            if ($newQty < (float) $level->reserved_qty - 0.0005) {
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

    public function reserve(Product $product, Warehouse $warehouse, float $quantity): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah reservasi harus lebih dari nol.',
            ]);
        }

        DB::transaction(function () use ($product, $warehouse, $quantity) {
            $level = StockLevel::query()->firstOrCreate(
                ['product_id' => $product->getKey(), 'warehouse_id' => $warehouse->getKey()],
                ['qty' => 0, 'reserved_qty' => 0],
            );

            $level = StockLevel::query()->whereKey($level->getKey())->lockForUpdate()->firstOrFail();

            if ($level->availableQty() + 0.0005 < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok {$product->name} di {$warehouse->name} tidak cukup. Tersedia {$level->availableQty()}.",
                ]);
            }

            $level->update([
                'reserved_qty' => round((float) $level->reserved_qty + $quantity, 3),
            ]);
        });
    }

    public function release(Product $product, Warehouse $warehouse, float $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($product, $warehouse, $quantity) {
            $level = StockLevel::query()
                ->where('product_id', $product->getKey())
                ->where('warehouse_id', $warehouse->getKey())
                ->lockForUpdate()
                ->first();

            if (! $level) {
                return;
            }

            $level->update([
                'reserved_qty' => max(0, round((float) $level->reserved_qty - $quantity, 3)),
            ]);
        });
    }

    public function commitReserved(
        Product $product,
        Warehouse $warehouse,
        float $quantity,
        ?float $unitCost = null,
        ?Model $reference = null,
        ?string $note = null,
        ?User $user = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah komitmen stok harus lebih dari nol.',
            ]);
        }

        return DB::transaction(function () use ($product, $warehouse, $quantity, $unitCost, $reference, $note, $user) {
            $level = StockLevel::query()
                ->where('product_id', $product->getKey())
                ->where('warehouse_id', $warehouse->getKey())
                ->lockForUpdate()
                ->first();

            if (! $level) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok {$product->name} di {$warehouse->name} tidak ditemukan.",
                ]);
            }

            if ((float) $level->qty + 0.0005 < $quantity || (float) $level->reserved_qty + 0.0005 < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Reservasi stok {$product->name} tidak mencukupi untuk dikomit.",
                ]);
            }

            $level->update([
                'qty' => round((float) $level->qty - $quantity, 3),
                'reserved_qty' => max(0, round((float) $level->reserved_qty - $quantity, 3)),
            ]);

            return StockMovement::query()->create([
                'product_id' => $product->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'type' => StockMovementType::SaleOnline,
                'quantity' => -1 * $quantity,
                'unit_cost' => $unitCost,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'note' => $note,
                'user_id' => $user?->getKey(),
                'occurred_at' => now(),
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
