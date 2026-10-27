<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Whether a customer account may buy on credit. The customer asks over WhatsApp,
 * the admin verifies the account in the panel.
 */
enum CreditVerification: string implements HasColor, HasLabel
{
    case Belum = 'none';
    case Diajukan = 'requested';
    case Terverifikasi = 'verified';
    case Ditolak = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Belum => 'Belum diajukan',
            self::Diajukan => 'Menunggu verifikasi',
            self::Terverifikasi => 'Terverifikasi',
            self::Ditolak => 'Ditolak',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Belum => 'gray',
            self::Diajukan => 'warning',
            self::Terverifikasi => 'success',
            self::Ditolak => 'danger',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Belum => 'bg-fog text-ink-2',
            self::Diajukan => 'bg-warn-soft text-warn',
            self::Terverifikasi => 'bg-good-soft text-good',
            self::Ditolak => 'bg-bad-soft text-bad',
        };
    }
}
