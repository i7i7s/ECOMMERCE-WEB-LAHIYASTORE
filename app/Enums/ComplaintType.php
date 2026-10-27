<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ComplaintType: string implements HasLabel
{
    case SalahKirim = 'salah_kirim';
    case SalahTipe = 'salah_tipe';
    case SalahCetak = 'salah_cetak';
    case Cacat = 'cacat';
    case RusakKirim = 'rusak_kirim';
    case Lainnya = 'lainnya';

    public function getLabel(): string
    {
        return match ($this) {
            self::SalahKirim => 'Salah kirim',
            self::SalahTipe => 'Salah tipe HP',
            self::SalahCetak => 'Salah cetak',
            self::Cacat => 'Cacat produksi',
            self::RusakKirim => 'Rusak saat pengiriman',
            self::Lainnya => 'Lainnya',
        };
    }
}
