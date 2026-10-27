<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How a product is sold: through the preorder cart, or on credit (installments) after
 * the customer's account is verified by the admin.
 */
enum ProductSaleMode: string implements HasLabel
{
    case Preorder = 'preorder';
    case Kredit = 'kredit';

    public function getLabel(): string
    {
        return match ($this) {
            self::Preorder => 'Preorder (keranjang)',
            self::Kredit => 'Kredit (cicilan)',
        };
    }
}
