<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CategoryScope: string implements HasLabel
{
    case Penjualan = 'penjualan';
    case Batch = 'batch';
    case Operasional = 'operasional';
    case Transfer = 'transfer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Penjualan => 'Penjualan',
            self::Batch => 'Biaya batch',
            self::Operasional => 'Operasional',
            self::Transfer => 'Transfer',
        };
    }
}
