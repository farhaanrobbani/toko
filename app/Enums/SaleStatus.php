<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Completed = 'completed';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Selesai',
            self::Void => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Completed => 'bg-green-50 text-green-700',
            self::Void => 'bg-red-50 text-red-700',
        };
    }
}
