<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Digits only, keeping a leading "+", so "080-1234 5678" and "08012345678" match.
     */
    public static function normalize(string $phone): string
    {
        $phone = trim($phone);
        $digits = preg_replace('/\D/', '', $phone);

        return (str_starts_with($phone, '+') ? '+' : '').$digits;
    }
}
