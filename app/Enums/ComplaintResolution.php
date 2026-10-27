<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ComplaintResolution: string implements HasLabel
{
    case CetakUlang = 'cetak_ulang';
    case Refund = 'refund';
    case Tolak = 'tolak';

    public function getLabel(): string
    {
        return match ($this) {
            self::CetakUlang => 'Cetak ulang',
            self::Refund => 'Refund',
            self::Tolak => 'Ditolak',
        };
    }
}
