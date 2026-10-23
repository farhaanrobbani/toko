<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'product_id',
    'warehouse_id',
    'type',
    'quantity',
    'unit_cost',
    'reference_type',
    'reference_id',
    'note',
    'user_id',
    'occurred_at',
])]
class StockMovement extends Model
{
    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
