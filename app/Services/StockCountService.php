<?php

namespace App\Services;

use App\Enums\StockCountStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\StockLevel;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockCountService
{
    public function __construct(private readonly StockService $stockService) {}

    public function create(Warehouse $warehouse, ?string $note, User $user): StockCount
    {
        return DB::transaction(function () use ($warehouse, $note, $user) {
            $count = StockCount::query()->create([
                'reference_number' => $this->nextReferenceNumber(),
                'warehouse_id' => $warehouse->getKey(),
                'status' => StockCountStatus::Draft,
                'note' => $note,
                'user_id' => $user->getKey(),
            ]);

            $levels = StockLevel::query()
                ->where('warehouse_id', $warehouse->getKey())
                ->pluck('qty', 'product_id');

            Product::query()
                ->active()
                ->orderBy('name')
                ->get()
                ->each(function (Product $product) use ($count, $levels) {
                    StockCountItem::query()->create([
                        'stock_count_id' => $count->getKey(),
                        'product_id' => $product->getKey(),
                        'product_name' => $product->name,
                        'sku' => $product->sku,
                        'system_qty' => (float) ($levels[$product->getKey()] ?? 0),
                    ]);
                });

            return $count->load('items');
        });
    }

    /**
     * @param  array<int, numeric-string|float|int|null>  $counted
     */
    public function saveCounts(StockCount $count, array $counted): StockCount
    {
        return DB::transaction(function () use ($count, $counted) {
            $count = StockCount::query()->whereKey($count->getKey())->lockForUpdate()->firstOrFail();

            if ($count->status !== StockCountStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya opname draft yang bisa diubah.',
                ]);
            }

            foreach ($count->items as $item) {
                $value = $counted[$item->getKey()] ?? null;

                if ($value === null || $value === '') {
                    continue;
                }

                $countedQty = round((float) $value, 3);

                if ($countedQty < 0) {
                    throw ValidationException::withMessages([
                        'counted' => "Jumlah fisik {$item->product_name} tidak boleh negatif.",
                    ]);
                }

                $item->update([
                    'counted_qty' => $countedQty,
                    'difference' => round($countedQty - (float) $item->system_qty, 3),
                ]);
            }

            return $count->refresh()->load('items');
        });
    }

    public function complete(StockCount $count, User $user): StockCount
    {
        return DB::transaction(function () use ($count, $user) {
            $count = StockCount::query()->whereKey($count->getKey())->lockForUpdate()->firstOrFail();

            if ($count->status !== StockCountStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Opname ini sudah selesai atau dibatalkan.',
                ]);
            }

            $count->load('items', 'warehouse');

            if ($count->items->contains(fn (StockCountItem $item) => $item->counted_qty === null)) {
                throw ValidationException::withMessages([
                    'counted' => 'Semua jumlah fisik harus diisi sebelum opname diselesaikan.',
                ]);
            }

            foreach ($count->items as $item) {
                $difference = round((float) $item->counted_qty - (float) $item->system_qty, 3);

                if (abs($difference) < 0.0005) {
                    continue;
                }

                if (! $item->product) {
                    throw ValidationException::withMessages([
                        'items' => "Produk {$item->product_name} sudah tidak tersedia.",
                    ]);
                }

                $this->stockService->record(
                    product: $item->product,
                    warehouse: $count->warehouse,
                    type: StockMovementType::Opname,
                    quantity: $difference,
                    unitCost: (float) $item->product->cost_price,
                    reference: $count,
                    note: "Opname {$count->reference_number}",
                    user: $user,
                );
            }

            $count->update([
                'status' => StockCountStatus::Completed,
                'counted_at' => now(),
            ]);

            return $count->refresh()->load('items');
        });
    }

    public function cancel(StockCount $count): StockCount
    {
        return DB::transaction(function () use ($count) {
            $count = StockCount::query()->whereKey($count->getKey())->lockForUpdate()->firstOrFail();

            if ($count->status !== StockCountStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya opname draft yang bisa dibatalkan.',
                ]);
            }

            $count->update(['status' => StockCountStatus::Cancelled]);

            return $count->refresh();
        });
    }

    private function nextReferenceNumber(): string
    {
        $prefix = 'OPN-'.now()->format('Ymd').'-';
        $count = StockCount::query()->where('reference_number', 'like', $prefix.'%')->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
