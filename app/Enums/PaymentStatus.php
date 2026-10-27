<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case Menunggu = 'menunggu';
    case Diterima = 'diterima';
    case Ditolak = 'ditolak';

    public function getLabel(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu verifikasi',
            self::Diterima => 'Diterima',
            self::Ditolak => 'Ditolak',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Menunggu => 'warning',
            self::Diterima => 'success',
            self::Ditolak => 'danger',
        };
    }
}
