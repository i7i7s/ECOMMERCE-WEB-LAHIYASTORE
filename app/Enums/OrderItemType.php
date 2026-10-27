<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OrderItemType: string implements HasLabel
{
    case Product = 'product';
    case CustomCase = 'custom_case';

    public function getLabel(): string
    {
        return match ($this) {
            self::Product => 'Produk',
            self::CustomCase => 'Custom casing',
        };
    }
}
