<?php

namespace App\Filament;

use Filament\Support\Contracts\HasLabel;

/**
 * Admin panel navigation groups, in display order.
 */
enum NavigationGroup implements HasLabel
{
    case Operasional;
    case Kredit;
    case Katalog;
    case CustomCasing;
    case Keuangan;
    case Pengaturan;

    public function getLabel(): string
    {
        return match ($this) {
            self::Operasional => 'Operasional',
            self::Kredit => 'Kredit HP',
            self::Katalog => 'Katalog',
            self::CustomCasing => 'Custom Casing',
            self::Keuangan => 'Keuangan',
            self::Pengaturan => 'Pengaturan',
        };
    }
}
