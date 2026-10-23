<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_number',
    'tracking_token',
    'status',
    'payment_method',
    'delivery_method',
    'customer_name',
    'customer_phone',
    'customer_address',
    'customer_note',
    'warehouse_id',
    'subtotal',
    'shipping_cost',
    'total',
    'payment_proof_path',
    'payment_uploaded_at',
    'confirmed_by',
    'confirmed_at',
    'stock_committed_at',
    'cancelled_at',
    'expires_at',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'delivery_method' => DeliveryMethod::class,
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'payment_uploaded_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'stock_committed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isPendingPayment(): bool
    {
        return $this->status === OrderStatus::PendingPayment;
    }

    public function isCancellable(): bool
    {
        return $this->status === OrderStatus::PendingPayment;
    }

    public function itemCount(): float
    {
        return (float) $this->items->sum('quantity');
    }
}
