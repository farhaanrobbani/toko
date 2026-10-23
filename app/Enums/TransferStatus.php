<?php

namespace App\Enums;

enum TransferStatus: string
{
    case Draft = 'draft';
    case InTransit = 'in_transit';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InTransit => 'Dalam Perjalanan',
            self::Received => 'Diterima',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
