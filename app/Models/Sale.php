<?php

namespace App\Models;

use App\Enums\PosPaymentMethod;
use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sale_number',
    'status',
    'payment_method',
    'warehouse_id',
    'cashier_id',
    'customer_name',
    'customer_phone',
    'subtotal',
    'discount',
    'total',
    'paid',
    'change',
    'note',
    'voided_by',
    'voided_at',
    'void_reason',
])]
class Sale extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SaleStatus::class,
            'payment_method' => PosPaymentMethod::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
            'change' => 'decimal:2',
            'voided_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function itemCount(): float
    {
        return (float) $this->items->sum('quantity');
    }
}
