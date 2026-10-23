<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Qris = 'qris';
    case Cod = 'cod';

    public function label(): string
    {
        return match ($this) {
            self::Qris => 'QRIS',
            self::Cod => 'Bayar di Tempat (COD)',
        };
    }
}
