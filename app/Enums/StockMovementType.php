<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Purchase = 'purchase';
    case SalePos = 'sale_pos';
    case SaleOnline = 'sale_online';
    case Adjustment = 'adjustment';
    case Opname = 'opname';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Return = 'return';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Barang Masuk',
            self::SalePos => 'Penjualan POS',
            self::SaleOnline => 'Penjualan Online',
            self::Adjustment => 'Penyesuaian',
            self::Opname => 'Stok Opname',
            self::TransferIn => 'Transfer Masuk',
            self::TransferOut => 'Transfer Keluar',
            self::Return => 'Retur',
            self::Void => 'Void',
        };
    }
}
