<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Credit purchase lifecycle: submitted → approved (waiting for the down payment)
 * → running (phone handed over, installments due) → paid off. Can be rejected or cancelled
 * before any payment is made.
 */
enum CreditApplicationStatus: string implements HasColor, HasLabel
{
    case Diajukan = 'diajukan';
    case Disetujui = 'disetujui';
    case Berjalan = 'berjalan';
    case Lunas = 'lunas';
    case Ditolak = 'ditolak';
    case Dibatalkan = 'dibatalkan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan',
            self::Disetujui => 'Disetujui, menunggu DP',
            self::Berjalan => 'Cicilan berjalan',
            self::Lunas => 'Lunas',
            self::Ditolak => 'Ditolak',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Diajukan => 'Admin sedang memeriksa pengajuanmu. Kamu akan dihubungi lewat WhatsApp.',
            self::Disetujui => 'Pengajuan disetujui. Bayar DP sesuai jadwal di bawah, lalu HP diserahkan.',
            self::Berjalan => 'HP sudah diserahkan. Bayar cicilan setiap bulan sesuai jadwal.',
            self::Lunas => 'Semua cicilan sudah dibayar. Terima kasih!',
            self::Ditolak => 'Pengajuan ini tidak disetujui.',
            self::Dibatalkan => 'Pengajuan ini dibatalkan.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Diajukan => 'warning',
            self::Disetujui => 'info',
            self::Berjalan => 'primary',
            self::Lunas => 'success',
            self::Ditolak => 'danger',
            self::Dibatalkan => 'gray',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Diajukan => 'bg-warn-soft text-warn',
            self::Disetujui => 'bg-info-soft text-info',
            self::Berjalan => 'bg-[#ecebfb] text-[#4338a8]',
            self::Lunas => 'bg-good-soft text-good',
            self::Ditolak => 'bg-bad-soft text-bad',
            self::Dibatalkan => 'bg-fog text-ink-2',
        };
    }

    /**
     * Statuses that still count against the customer's credit limit.
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Diajukan, self::Disetujui, self::Berjalan];
    }
}
