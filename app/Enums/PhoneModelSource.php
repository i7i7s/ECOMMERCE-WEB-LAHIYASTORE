<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PhoneModelSource: string implements HasLabel
{
    case Api = 'api';
    case Katalog = 'katalog';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Api => 'API data HP',
            self::Katalog => 'Daftar bawaan',
            self::Manual => 'Manual',
        };
    }
}
