<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case TransferManual = 'transfer_manual';
    case Gateway = 'gateway';

    public function getLabel(): string
    {
        return match ($this) {
            self::TransferManual => 'Transfer manual',
            self::Gateway => 'Payment gateway',
        };
    }
}
