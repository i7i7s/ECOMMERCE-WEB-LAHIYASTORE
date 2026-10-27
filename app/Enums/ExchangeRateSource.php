<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExchangeRateSource: string implements HasColor, HasLabel
{
    case Manual = 'manual';
    case Api = 'api';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Api => 'API',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Manual => 'gray',
            self::Api => 'info',
        };
    }
}
