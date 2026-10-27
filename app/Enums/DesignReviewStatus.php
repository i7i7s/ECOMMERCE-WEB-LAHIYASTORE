<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DesignReviewStatus: string implements HasColor, HasLabel
{
    case Menunggu = 'menunggu';
    case Disetujui = 'disetujui';
    case Revisi = 'revisi';

    public function getLabel(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu review',
            self::Disetujui => 'Disetujui',
            self::Revisi => 'Revisi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Menunggu => 'warning',
            self::Disetujui => 'success',
            self::Revisi => 'danger',
        };
    }
}
