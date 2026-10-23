<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu Pembayaran',
            self::Paid => 'Dibayar',
            self::Processing => 'Diproses',
            self::Shipped => 'Dikirim',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PendingPayment => 'bg-amber-50 text-amber-700',
            self::Paid => 'bg-blue-50 text-blue-700',
            self::Processing => 'bg-indigo-50 text-indigo-700',
            self::Shipped => 'bg-purple-50 text-purple-700',
            self::Completed => 'bg-green-50 text-green-700',
            self::Cancelled => 'bg-red-50 text-red-700',
        };
    }

    public function canAdvanceTo(self $next): bool
    {
        return match ($this) {
            self::Paid => $next === self::Processing,
            self::Processing => $next === self::Shipped,
            self::Shipped => $next === self::Completed,
            default => false,
        };
    }
}
