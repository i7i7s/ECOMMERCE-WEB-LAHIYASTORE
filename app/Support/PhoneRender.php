<?php

namespace App\Support;

/**
 * Product picture of a phone in one colour: front and back side by side, drawn from the
 * PhoneMockups dimensions. Used for the phones sold on credit until the admin uploads photos.
 */
class PhoneRender
{
    public static function svg(string $model, string $bodyHex): string
    {
        $mockup = PhoneMockups::bare(PhoneMockups::for($model), $bodyHex);

        return view('renders.phone-product', [
            'mockup' => $mockup,
            'wallpaperFrom' => self::shade($bodyHex, 0.35),
            'wallpaperTo' => self::shade($bodyHex, -0.45),
        ])->render();
    }

    /**
     * Mixes a hex colour with white (positive amount) or black (negative amount).
     */
    public static function shade(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');
        $target = $amount >= 0 ? 255 : 0;
        $amount = abs($amount);

        return '#'.implode('', array_map(
            fn (string $channel): string => str_pad(dechex((int) round(hexdec($channel) + ($target - hexdec($channel)) * $amount)), 2, '0', STR_PAD_LEFT),
            str_split($hex, 2),
        ));
    }
}
