<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CashAccountType: string implements HasLabel
{
    case Bank = 'bank';
    case Tunai = 'tunai';
    case Gateway = 'gateway';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bank => 'Bank',
            self::Tunai => 'Tunai',
            self::Gateway => 'Saldo gateway',
        };
    }
}
