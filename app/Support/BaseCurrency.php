<?php

namespace App\Support;

use App\Enums\Currency;
use App\Models\Setting;

/**
 * Converts amounts into the reporting currency (PRD §6.4, default IDR) using a JPY→IDR rate.
 */
class BaseCurrency
{
    public static function currency(): Currency
    {
        return Currency::tryFrom((string) Setting::valueOf('base_currency', Currency::Idr->value)) ?? Currency::Idr;
    }

    public static function convert(int $amount, Currency $from, float $jpyToIdr): int
    {
        $base = self::currency();

        return match (true) {
            $from === $base => $amount,
            $from === Currency::Jpy => (int) round($amount * $jpyToIdr),
            default => (int) round($amount / $jpyToIdr),
        };
    }
}
