<?php

namespace App\Models;

use App\Enums\StockCountStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'reference_number',
    'warehouse_id',
    'status',
    'note',
    'user_id',
    'counted_at',
])]
class StockCount extends Model
{
    protected function casts(): array
    {
        return [
            'status' => StockCountStatus::class,
            'counted_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class);
    }
}
