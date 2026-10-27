<?php

namespace App\Enums;

enum Currency: string
{
    case Jpy = 'JPY';
    case Idr = 'IDR';

    /**
     * The currency the visitor picked, stored in the session (default JPY).
     */
    public static function current(): self
    {
        return self::tryFrom((string) session('currency')) ?? self::Jpy;
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Jpy => '¥',
            self::Idr => 'Rp',
        };
    }

    /**
     * Format an integer amount, e.g. ¥2.800 or Rp450.000.
     */
    public function format(int $amount): string
    {
        return $this->symbol().number_format($amount, 0, ',', '.');
    }
}
