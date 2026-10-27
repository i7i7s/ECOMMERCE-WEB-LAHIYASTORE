<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Preorder batch lifecycle from PRD §6.1.
 */
enum BatchStatus: string implements HasColor, HasLabel
{
    case Dibuka = 'dibuka';
    case Ditutup = 'ditutup';
    case Diproduksi = 'diproduksi';
    case DikirimKeJepang = 'dikirim_ke_jepang';
    case TibaDiJepang = 'tiba_di_jepang';
    case Selesai = 'selesai';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dibuka => 'Dibuka',
            self::Ditutup => 'Ditutup',
            self::Diproduksi => 'Diproduksi',
            self::DikirimKeJepang => 'Dikirim ke Jepang',
            self::TibaDiJepang => 'Tiba di Jepang',
            self::Selesai => 'Selesai',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Dibuka, self::Selesai => 'success',
            self::Ditutup => 'gray',
            self::Diproduksi => 'primary',
            self::DikirimKeJepang, self::TibaDiJepang => 'info',
        };
    }

    /**
     * The only status a batch may move to next, or null when finished.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Dibuka => self::Ditutup,
            self::Ditutup => self::Diproduksi,
            self::Diproduksi => self::DikirimKeJepang,
            self::DikirimKeJepang => self::TibaDiJepang,
            self::TibaDiJepang => self::Selesai,
            self::Selesai => null,
        };
    }

    /**
     * Status that paid orders inside the batch take when the batch reaches this status.
     */
    public function orderStatus(): ?OrderStatus
    {
        return match ($this) {
            self::Diproduksi => OrderStatus::Diproduksi,
            self::DikirimKeJepang => OrderStatus::DikirimKeJepang,
            self::TibaDiJepang => OrderStatus::TibaDiJepang,
            default => null,
        };
    }
}
