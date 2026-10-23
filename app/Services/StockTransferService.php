<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Enums\TransferStatus;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function __construct(private readonly StockService $stockService) {}

    /**
     * @param  array<int, array{product_id: int, quantity: numeric-string|float|int}>  $items
     */
    public function create(int $fromWarehouseId, int $toWarehouseId, array $items, ?string $note, User $user): StockTransfer
    {
        if ($fromWarehouseId === $toWarehouseId) {
            throw ValidationException::withMessages([
                'to_warehouse_id' => 'Lokasi asal dan tujuan harus berbeda.',
            ]);
        }

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'Minimal satu produk harus diisi.',
            ]);
        }

        $from = Warehouse::query()->whereKey($fromWarehouseId)->firstOrFail();
        $to = Warehouse::query()->whereKey($toWarehouseId)->firstOrFail();

        return DB::transaction(function () use ($from, $to, $items, $note, $user) {
            $transfer = StockTransfer::query()->create([
                'reference_number' => $this->nextReferenceNumber(),
                'from_warehouse_id' => $from->getKey(),
                'to_warehouse_id' => $to->getKey(),
                'status' => TransferStatus::Draft,
                'note' => $note,
                'user_id' => $user->getKey(),
            ]);

            foreach ($items as $item) {
                $quantity = round((float) $item['quantity'], 3);

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Jumlah transfer harus lebih dari nol.',
                    ]);
                }

                $product = Product::query()->findOrFail($item['product_id']);

                StockTransferItem::query()->create([
                    'stock_transfer_id' => $transfer->getKey(),
                    'product_id' => $product->getKey(),
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                ]);
            }

            return $transfer->load('items');
        });
    }

    public function ship(StockTransfer $transfer, User $user): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $user) {
            $transfer = StockTransfer::query()->whereKey($transfer->getKey())->lockForUpdate()->firstOrFail();

            if ($transfer->status !== TransferStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transfer draft yang bisa dikirim.',
                ]);
            }

            $transfer->load('items', 'fromWarehouse');

            foreach ($transfer->items as $item) {
                if (! $item->product) {
                    throw ValidationException::withMessages([
                        'items' => "Produk {$item->product_name} sudah tidak tersedia.",
                    ]);
                }

                $this->stockService->record(
                    product: $item->product,
                    warehouse: $transfer->fromWarehouse,
                    type: StockMovementType::TransferOut,
                    quantity: -1 * (float) $item->quantity,
                    unitCost: (float) $item->product->cost_price,
                    reference: $transfer,
                    note: "Transfer {$transfer->reference_number}",
                    user: $user,
                );
            }

            $transfer->update([
                'status' => TransferStatus::InTransit,
                'shipped_at' => now(),
            ]);

            return $transfer->refresh()->load('items');
        });
    }

    public function receive(StockTransfer $transfer, User $user): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $user) {
            $transfer = StockTransfer::query()->whereKey($transfer->getKey())->lockForUpdate()->firstOrFail();

            if ($transfer->status !== TransferStatus::InTransit) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transfer dalam perjalanan yang bisa diterima.',
                ]);
            }

            $transfer->load('items', 'toWarehouse');

            foreach ($transfer->items as $item) {
                if (! $item->product) {
                    throw ValidationException::withMessages([
                        'items' => "Produk {$item->product_name} sudah tidak tersedia.",
                    ]);
                }

                $this->stockService->record(
                    product: $item->product,
                    warehouse: $transfer->toWarehouse,
                    type: StockMovementType::TransferIn,
                    quantity: (float) $item->quantity,
                    unitCost: (float) $item->product->cost_price,
                    reference: $transfer,
                    note: "Transfer {$transfer->reference_number}",
                    user: $user,
                );
            }

            $transfer->update([
                'status' => TransferStatus::Received,
                'received_at' => now(),
            ]);

            return $transfer->refresh()->load('items');
        });
    }

    public function cancel(StockTransfer $transfer): StockTransfer
    {
        return DB::transaction(function () use ($transfer) {
            $transfer = StockTransfer::query()->whereKey($transfer->getKey())->lockForUpdate()->firstOrFail();

            if ($transfer->status !== TransferStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transfer draft yang bisa dibatalkan.',
                ]);
            }

            $transfer->update(['status' => TransferStatus::Cancelled]);

            return $transfer->refresh();
        });
    }

    private function nextReferenceNumber(): string
    {
        $prefix = 'TRF-'.now()->format('Ymd').'-';
        $count = StockTransfer::query()->where('reference_number', 'like', $prefix.'%')->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
