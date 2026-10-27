<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ComplaintStatus: string implements HasColor, HasLabel
{
    case Terbuka = 'terbuka';
    case Selesai = 'selesai';

    public function getLabel(): string
    {
        return match ($this) {
            self::Terbuka => 'Terbuka',
            self::Selesai => 'Selesai',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Terbuka => 'warning',
            self::Selesai => 'success',
        };
    }
}
