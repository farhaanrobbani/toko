<?php

namespace App\Models;

use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'reference_number',
    'from_warehouse_id',
    'to_warehouse_id',
    'status',
    'note',
    'user_id',
    'shipped_at',
    'received_at',
])]
class StockTransfer extends Model
{
    protected function casts(): array
    {
        return [
            'status' => TransferStatus::class,
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
