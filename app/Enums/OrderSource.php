<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OrderSource: string implements HasLabel
{
    case Website = 'website';
    case Whatsapp = 'whatsapp';
    case Tiktok = 'tiktok';
    case Penggantian = 'penggantian';

    public function getLabel(): string
    {
        return match ($this) {
            self::Website => 'Website',
            self::Whatsapp => 'WhatsApp',
            self::Tiktok => 'TikTok',
            self::Penggantian => 'Penggantian',
        };
    }
}
