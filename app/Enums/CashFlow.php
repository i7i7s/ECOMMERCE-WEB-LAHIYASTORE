<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Direction of money. Transactions are always Masuk or Keluar; Transfer only describes a category.
 */
enum CashFlow: string implements HasColor, HasLabel
{
    case Masuk = 'masuk';
    case Keluar = 'keluar';
    case Transfer = 'transfer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Masuk => 'Masuk',
            self::Keluar => 'Keluar',
            self::Transfer => 'Transfer',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Masuk => 'success',
            self::Keluar => 'danger',
            self::Transfer => 'gray',
        };
    }
}
