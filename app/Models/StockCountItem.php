<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_count_id',
    'product_id',
    'product_name',
    'sku',
    'system_qty',
    'counted_qty',
    'difference',
])]
class StockCountItem extends Model
{
    protected function casts(): array
    {
        return [
            'system_qty' => 'decimal:3',
            'counted_qty' => 'decimal:3',
            'difference' => 'decimal:3',
        ];
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
