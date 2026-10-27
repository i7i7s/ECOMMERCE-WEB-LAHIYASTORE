<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Customer = 'customer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Staff => 'Staff',
            self::Customer => 'Pelanggan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Staff => 'info',
            self::Customer => 'gray',
        };
    }
}
