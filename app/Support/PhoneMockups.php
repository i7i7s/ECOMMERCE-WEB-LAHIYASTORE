<?php

namespace App\Support;

/**
 * 2D back views of the phone models that have a preview, drawn over the customer's design in
 * the custom case editor. All values are millimetres on the back of the phone, seen from behind,
 * origin top-left. Body sizes are the official dimensions; camera layouts follow the real
 * phones (Apple and Samsung do not publish them, so they are measured from product photos).
 *
 * @phpstan-type Circle array{float, float, float} cx, cy, r
 * @phpstan-type Rect array{float, float, float, float, float} x, y, width, height, corner radius
 * @phpstan-type Mockup array{width: float, height: float, radius: float, body: string, front: 'island'|'notch', holes: list<Rect>, roundHoles: list<Circle>, islands: list<Rect>, lenses: list<Circle>, periscopes: list<Circle>, flashes: list<Circle>, sensors: list<Circle>, dots: list<Circle>}
 */
class PhoneMockups
{
    /** @var array<string, array<string, mixed>> Keyed by phone model slug. */
    private const SPECS = [
        // Full-width aluminium camera plateau; case exposes the whole plateau.
        'iphone-17-pro-max' => [
            'width' => 78.0, 'height' => 163.4, 'radius' => 12.5, 'body' => '#d9773a',
            'holes' => [[2.0, 2.0, 74.0, 37.5, 10.0]],
            'lenses' => [[12.9, 12.2, 7.7], [12.9, 29.6, 7.7], [28.0, 20.9, 7.7]],
            'flashes' => [[65.4, 12.4, 3.0]],
            'sensors' => [[65.4, 28.9, 3.0]],
            'dots' => [[57.4, 20.9, 0.8]],
        ],
        'iphone-17-pro' => [
            'width' => 71.9, 'height' => 150.0, 'radius' => 11.6, 'body' => '#d9773a',
            'holes' => [[2.0, 2.0, 67.9, 36.4, 9.6]],
            'lenses' => [[12.6, 11.9, 7.4], [12.6, 28.7, 7.4], [27.2, 20.3, 7.4]],
            'flashes' => [[59.6, 12.0, 2.9]],
            'sensors' => [[59.6, 28.2, 2.9]],
            'dots' => [[51.8, 20.3, 0.8]],
        ],
        // Vertical pill with two lenses, flash outside the pill.
        'iphone-17' => [
            'width' => 71.5, 'height' => 149.6, 'radius' => 11.2, 'body' => '#cdbfe3',
            'holes' => [[3.8, 3.8, 29.2, 39.0, 10.5]],
            'islands' => [[6.2, 6.2, 17.0, 34.2, 8.5]],
            'lenses' => [[14.7, 14.8, 6.6], [14.7, 31.6, 6.6]],
            'flashes' => [[28.4, 13.6, 2.4]],
            'dots' => [[28.4, 21.6, 0.7]],
        ],
        'iphone-16' => [
            'width' => 71.6, 'height' => 147.6, 'radius' => 11.2, 'body' => '#7f8fe0',
            'holes' => [[3.8, 3.8, 29.2, 39.0, 10.5]],
            'islands' => [[6.2, 6.2, 17.0, 34.2, 8.5]],
            'lenses' => [[14.7, 14.8, 6.6], [14.7, 31.6, 6.6]],
            'flashes' => [[28.4, 13.6, 2.4]],
            'dots' => [[28.4, 21.6, 0.7]],
        ],
        // Square glass camera bump, three lenses in a triangle, flash and LiDAR on the right.
        'iphone-16-pro-max' => [
            'width' => 77.6, 'height' => 163.0, 'radius' => 12.3, 'body' => '#c9b59c',
            'holes' => [[3.6, 3.6, 41.8, 41.8, 11.5]],
            'islands' => [[5.6, 5.6, 37.8, 37.8, 9.6]],
            'lenses' => [[15.0, 15.0, 8.0], [15.0, 34.0, 8.0], [32.4, 24.5, 8.0]],
            'flashes' => [[34.6, 11.0, 2.4]],
            'sensors' => [[34.6, 38.0, 2.4]],
            'dots' => [[25.0, 9.0, 0.6]],
        ],
        // Individual lens rings, no camera island. Layout per Samsung: laser AF above the flash, flash above the 3x lens.
        'samsung-galaxy-s25-ultra' => [
            'width' => 77.6, 'height' => 162.8, 'radius' => 9.0, 'body' => '#b9c5d0',
            'roundHoles' => [[13.6, 13.8, 7.5], [13.6, 29.2, 7.5], [13.6, 44.6, 7.5]],
            'holes' => [[22.2, 9.0, 12.4, 35.4, 6.2]],
            // Left: ultra wide, main, 5x periscope. Right: laser AF, flash, 3x telephoto.
            'lenses' => [[13.6, 13.8, 6.4], [13.6, 29.2, 6.4], [28.4, 34.2, 4.6]],
            'periscopes' => [[13.6, 44.6, 6.4]],
            'flashes' => [[28.4, 22.4, 2.2]],
            'sensors' => [[28.4, 13.6, 1.7]],
        ],
        'samsung-galaxy-s25' => [
            'width' => 70.5, 'height' => 146.9, 'radius' => 10.0, 'body' => '#cad9e7',
            'holes' => [[5.5, 6.3, 13.8, 40.3, 6.9]],
            'roundHoles' => [[24.0, 13.2, 2.9]],
            'lenses' => [[12.4, 13.2, 5.7], [12.4, 26.4, 5.7], [12.4, 39.6, 5.7]],
            'flashes' => [[24.0, 13.2, 1.8]],
        ],
        // Same layout as the iPhone 17 Pro family.
        'iphone-18-pro-max' => [
            'width' => 78.0, 'height' => 163.4, 'radius' => 12.5, 'body' => '#6b2a35',
            'holes' => [[2.0, 2.0, 74.0, 37.5, 10.0]],
            'lenses' => [[12.9, 12.2, 7.7], [12.9, 29.6, 7.7], [28.0, 20.9, 7.7]],
            'flashes' => [[65.4, 12.4, 3.0]],
            'sensors' => [[65.4, 28.9, 3.0]],
            'dots' => [[57.4, 20.9, 0.8]],
        ],
        'iphone-18-pro' => [
            'width' => 71.9, 'height' => 150.0, 'radius' => 11.6, 'body' => '#6b2a35',
            'holes' => [[2.0, 2.0, 67.9, 36.4, 9.6]],
            'lenses' => [[12.6, 11.9, 7.4], [12.6, 28.7, 7.4], [27.2, 20.3, 7.4]],
            'flashes' => [[59.6, 12.0, 2.9]],
            'sensors' => [[59.6, 28.2, 2.9]],
            'dots' => [[51.8, 20.3, 0.8]],
        ],
        // Horizontal camera bar across the top with a single lens.
        'iphone-air' => [
            'width' => 74.7, 'height' => 156.2, 'radius' => 11.8, 'body' => '#c5daea',
            'holes' => [[2.0, 2.0, 70.7, 22.0, 9.0]],
            'islands' => [[3.4, 3.4, 67.9, 19.2, 8.0]],
            'lenses' => [[13.4, 13.0, 6.3]],
            'flashes' => [[26.4, 13.0, 2.1]],
            'dots' => [[32.0, 13.0, 0.6]],
        ],
        'iphone-16-plus' => [
            'width' => 77.8, 'height' => 160.9, 'radius' => 12.2, 'body' => '#7f8fe0',
            'holes' => [[3.8, 3.8, 29.2, 39.0, 10.5]],
            'islands' => [[6.2, 6.2, 17.0, 34.2, 8.5]],
            'lenses' => [[14.7, 14.8, 6.6], [14.7, 31.6, 6.6]],
            'flashes' => [[28.4, 13.6, 2.4]],
            'dots' => [[28.4, 21.6, 0.7]],
        ],
        'iphone-16-pro' => [
            'width' => 71.5, 'height' => 149.6, 'radius' => 11.3, 'body' => '#c9b59c',
            'holes' => [[3.4, 3.4, 38.6, 38.6, 10.6]],
            'islands' => [[5.4, 5.4, 34.6, 34.6, 8.8]],
            'lenses' => [[14.2, 14.2, 7.3], [14.2, 31.6, 7.3], [30.2, 22.9, 7.3]],
            'flashes' => [[32.2, 10.6, 2.2]],
            'sensors' => [[32.2, 35.2, 2.2]],
            'dots' => [[23.2, 8.4, 0.6]],
        ],
        // Single camera in a small square bump, flash beside it; notch on the front.
        'iphone-16e' => [
            'width' => 71.5, 'height' => 146.7, 'radius' => 11.0, 'body' => '#f5f5f0', 'front' => 'notch',
            'holes' => [[4.0, 4.0, 27.0, 19.5, 6.0]],
            'islands' => [[6.0, 6.0, 15.5, 15.5, 4.6]],
            'lenses' => [[13.75, 13.75, 5.4]],
            'flashes' => [[26.2, 9.4, 1.8]],
            'dots' => [[26.2, 15.6, 0.6]],
        ],
        'iphone-17e' => [
            'width' => 71.5, 'height' => 146.7, 'radius' => 11.0, 'body' => '#f4d6dc', 'front' => 'notch',
            'holes' => [[4.0, 4.0, 27.0, 19.5, 6.0]],
            'islands' => [[6.0, 6.0, 15.5, 15.5, 4.6]],
            'lenses' => [[13.75, 13.75, 5.4]],
            'flashes' => [[26.2, 9.4, 1.8]],
            'dots' => [[26.2, 15.6, 0.6]],
        ],
        // iPhone 15 Pro family: same square bump and triangle of lenses as the 16 Pro.
        'iphone-15-pro-max' => [
            'width' => 76.7, 'height' => 159.9, 'radius' => 12.0, 'body' => '#bebab0',
            'holes' => [[3.6, 3.6, 41.8, 41.8, 11.5]],
            'islands' => [[5.6, 5.6, 37.8, 37.8, 9.6]],
            'lenses' => [[15.0, 15.0, 8.0], [15.0, 34.0, 8.0], [32.4, 24.5, 8.0]],
            'flashes' => [[34.6, 11.0, 2.4]],
            'sensors' => [[34.6, 38.0, 2.4]],
            'dots' => [[25.0, 9.0, 0.6]],
        ],
        'iphone-15-pro' => [
            'width' => 70.6, 'height' => 146.6, 'radius' => 11.0, 'body' => '#3b4558',
            'holes' => [[3.4, 3.4, 38.6, 38.6, 10.6]],
            'islands' => [[5.4, 5.4, 34.6, 34.6, 8.8]],
            'lenses' => [[14.2, 14.2, 7.3], [14.2, 31.6, 7.3], [30.2, 22.9, 7.3]],
            'flashes' => [[32.2, 10.6, 2.2]],
            'sensors' => [[32.2, 35.2, 2.2]],
            'dots' => [[23.2, 8.4, 0.6]],
        ],
        // iPhone 13, 14 and 15: two lenses on a diagonal in a square bump, flash top right.
        'iphone-15' => [
            'width' => 71.6, 'height' => 147.6, 'radius' => 11.0, 'body' => '#f2e6a8',
            'holes' => [[4.2, 4.2, 28.0, 28.0, 8.5]],
            'islands' => [[6.0, 6.0, 24.4, 24.4, 7.0]],
            'lenses' => [[12.2, 12.2, 5.4], [24.0, 24.0, 5.4]],
            'flashes' => [[24.6, 11.8, 2.0]],
        ],
        'iphone-14' => [
            'width' => 71.5, 'height' => 146.7, 'radius' => 11.0, 'body' => '#d9cfe8',
            'holes' => [[4.2, 4.2, 28.0, 28.0, 8.5]],
            'islands' => [[6.0, 6.0, 24.4, 24.4, 7.0]],
            'lenses' => [[12.2, 12.2, 5.4], [24.0, 24.0, 5.4]],
            'flashes' => [[24.6, 11.8, 2.0]],
        ],
        'iphone-13' => [
            'width' => 71.5, 'height' => 146.7, 'radius' => 11.0, 'body' => '#f4c6c6',
            'holes' => [[4.2, 4.2, 28.0, 28.0, 8.5]],
            'islands' => [[6.0, 6.0, 24.4, 24.4, 7.0]],
            'lenses' => [[12.2, 12.2, 5.4], [24.0, 24.0, 5.4]],
            'flashes' => [[24.6, 11.8, 2.0]],
        ],
        // Galaxy S24: same camera layout as the S25 family.
        'samsung-galaxy-s24-ultra' => [
            'width' => 79.0, 'height' => 162.3, 'radius' => 7.0, 'body' => '#a8a49c',
            'roundHoles' => [[13.6, 13.8, 7.5], [13.6, 29.2, 7.5], [13.6, 44.6, 7.5]],
            'holes' => [[22.2, 9.0, 12.4, 35.4, 6.2]],
            'lenses' => [[13.6, 13.8, 6.4], [13.6, 29.2, 6.4], [28.4, 34.2, 4.6]],
            'periscopes' => [[13.6, 44.6, 6.4]],
            'flashes' => [[28.4, 22.4, 2.2]],
            'sensors' => [[28.4, 13.6, 1.7]],
        ],
        'samsung-galaxy-s24' => [
            'width' => 70.6, 'height' => 147.0, 'radius' => 10.0, 'body' => '#cbbfe6',
            'holes' => [[5.5, 6.3, 13.8, 40.3, 6.9]],
            'roundHoles' => [[24.0, 13.2, 2.9]],
            'lenses' => [[12.4, 13.2, 5.7], [12.4, 26.4, 5.7], [12.4, 39.6, 5.7]],
            'flashes' => [[24.0, 13.2, 1.8]],
        ],
        // Pixel 8 and 9: camera bar running edge to edge across the top.
        'google-pixel-9-pro' => [
            'width' => 72.0, 'height' => 152.8, 'radius' => 10.5, 'body' => '#d9d3c7',
            'holes' => [[0.0, 12.0, 72.0, 18.0, 1.0]],
            'lenses' => [[14.0, 21.0, 5.2], [27.5, 21.0, 4.6], [40.5, 21.0, 4.6]],
            'flashes' => [[53.0, 19.0, 2.0]],
            'sensors' => [[53.0, 25.0, 1.2]],
        ],
        'google-pixel-9' => [
            'width' => 72.0, 'height' => 152.8, 'radius' => 10.5, 'body' => '#e9bdc6',
            'holes' => [[0.0, 12.0, 72.0, 18.0, 1.0]],
            'lenses' => [[16.0, 21.0, 5.4], [31.5, 21.0, 4.8]],
            'flashes' => [[46.0, 19.0, 2.0]],
            'sensors' => [[46.0, 25.0, 1.2]],
        ],
        'google-pixel-8' => [
            'width' => 70.8, 'height' => 150.5, 'radius' => 10.5, 'body' => '#3f444b',
            'holes' => [[0.0, 12.0, 70.8, 18.0, 1.0]],
            'lenses' => [[15.5, 21.0, 5.4], [31.0, 21.0, 4.8]],
            'flashes' => [[45.0, 19.0, 2.0]],
            'sensors' => [[45.0, 25.0, 1.2]],
        ],
    ];

    /**
     * The mockup of a phone model, or a generic back for preview models drawn by the admin later.
     *
     * @return Mockup
     */
    public static function for(?string $slug, ?float $widthMm = null, ?float $heightMm = null): array
    {
        $spec = self::SPECS[$slug] ?? self::generic($widthMm ?? 75.0, $heightMm ?? 155.0);

        return [
            'front' => 'island',
            'holes' => [], 'roundHoles' => [], 'islands' => [], 'lenses' => [], 'periscopes' => [],
            'flashes' => [], 'sensors' => [], 'dots' => [],
            ...$spec,
        ];
    }

    /**
     * The phone without a case: no cutouts. A full-width camera plateau (iPhone 17 Pro family)
     * is drawn as a raised bar from edge to edge; separate camera islands stay as they are.
     *
     * @param  Mockup  $mockup
     * @return Mockup
     */
    public static function bare(array $mockup, ?string $body = null): array
    {
        $plateau = $mockup['islands'] === [] && $mockup['holes'] !== [] && $mockup['holes'][0][2] > $mockup['width'] * 0.8;

        return [
            ...$mockup,
            'body' => $body ?? $mockup['body'],
            'holes' => $plateau ? [[0.0, 0.0, $mockup['width'], $mockup['holes'][0][1] + $mockup['holes'][0][3] + 0.5, $mockup['radius']]] : [],
            'roundHoles' => [],
        ];
    }

    public static function has(?string $slug): bool
    {
        return isset(self::SPECS[$slug]);
    }

    /**
     * SVG path of the case cutouts, shifted by the case rim. Drawn with fill-rule="evenodd"
     * after an outer rectangle, it punches real holes in the case (used as a CSS mask).
     *
     * @param  Mockup  $mockup
     */
    public static function cutoutPath(array $mockup, float $offsetX = 0, float $offsetY = 0): string
    {
        $path = '';

        foreach ($mockup['holes'] as [$x, $y, $width, $height, $radius]) {
            $x += $offsetX;
            $y += $offsetY;
            $radius = min($radius, $width / 2, $height / 2);
            $path .= sprintf(
                'M%1$s %2$sH%3$sA%9$s %9$s 0 0 1 %4$s %5$sV%6$sA%9$s %9$s 0 0 1 %3$s %7$sH%1$sA%9$s %9$s 0 0 1 %8$s %6$sV%5$sA%9$s %9$s 0 0 1 %1$s %2$sZ',
                round($x + $radius, 3), round($y, 3), round($x + $width - $radius, 3), round($x + $width, 3), round($y + $radius, 3),
                round($y + $height - $radius, 3), round($y + $height, 3), round($x, 3), round($radius, 3),
            );
        }

        foreach ($mockup['roundHoles'] as [$cx, $cy, $radius]) {
            $path .= sprintf('M%s %sa%3$s %3$s 0 1 0 %4$s 0a%3$s %3$s 0 1 0 -%4$s 0Z', round($cx + $offsetX - $radius, 3), round($cy + $offsetY, 3), round($radius, 3), round($radius * 2, 3));
        }

        return $path;
    }

    /**
     * Slugs of the phone models with a drawn mockup.
     *
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::SPECS);
    }

    /**
     * A likely back for a phone without a drawn mockup, guessed from its name: size class
     * (Pro Max / Ultra / Plus are large, mini / SE / lite are small) and the camera style of
     * the brand family. Shown to customers as an approximate preview.
     *
     * @return Mockup
     */
    public static function approximate(string $name): array
    {
        $name = mb_strtolower($name);

        [$width, $height] = match (true) {
            (bool) preg_match('/pro max|ultra|plus|\+|\bmax\b|\bxl\b|note|fold|\bbig\b/', $name) => [77.5, 162.5],
            (bool) preg_match('/mini|\bse\b|\blite\b|\d+e\b|\bxr\b|compact|flip|basio|wish/', $name) => [70.5, 146.5],
            default => [73.5, 153.5],
        };

        $spec = match (true) {
            str_contains($name, 'iphone') && str_contains($name, 'pro') => self::squareBump($width, $height, triple: true),
            str_contains($name, 'iphone') && (bool) preg_match('/\bse\b|\bxr\b|\d+e\b/', $name) => self::singleLens($width, $height),
            str_contains($name, 'iphone') => self::squareBump($width, $height, triple: false),
            str_contains($name, 'pixel') => self::visor($width, $height),
            str_contains($name, 'galaxy') && str_contains($name, 'ultra') => self::lensColumn($width, $height, 3, ring: 6.2),
            str_contains($name, 'galaxy') => self::lensColumn($width, $height, 3, ring: 5.4),
            str_contains($name, 'xperia') => self::lensColumn($width, $height, 3, ring: 4.4),
            default => self::generic($width, $height),
        };

        return [
            'front' => 'island',
            'holes' => [], 'roundHoles' => [], 'islands' => [], 'lenses' => [], 'periscopes' => [],
            'flashes' => [], 'sensors' => [], 'dots' => [],
            'body' => '#3a3a3c',
            ...$spec,
        ];
    }

    /**
     * Square glass bump top-left: two lenses on a diagonal (iPhone 11–15) or three in a triangle (Pro).
     *
     * @return array<string, mixed>
     */
    private static function squareBump(float $width, float $height, bool $triple): array
    {
        $size = round($width * ($triple ? 0.5 : 0.42), 1);
        $start = 5.5;
        $lens = round($size * ($triple ? 0.2 : 0.21), 1);

        return [
            'width' => $width, 'height' => $height, 'radius' => round($width * 0.155, 1),
            'holes' => [[$start - 2, $start - 2, $size + 4, $size + 4, $size * 0.3]],
            'islands' => [[$start, $start, $size, $size, $size * 0.25]],
            'lenses' => $triple
                ? [[$start + $size * 0.27, $start + $size * 0.27, $lens], [$start + $size * 0.27, $start + $size * 0.75, $lens], [$start + $size * 0.72, $start + $size * 0.51, $lens]]
                : [[$start + $size * 0.3, $start + $size * 0.3, $lens], [$start + $size * 0.7, $start + $size * 0.7, $lens]],
            'flashes' => [[$start + $size * ($triple ? 0.76 : 0.72), $start + $size * ($triple ? 0.17 : 0.27), $lens * 0.32]],
            'sensors' => $triple ? [[$start + $size * 0.76, $start + $size * 0.86, $lens * 0.32]] : [],
        ];
    }

    /**
     * One lens in a small square bump with the flash beside it (iPhone SE, XR, 16e).
     *
     * @return array<string, mixed>
     */
    private static function singleLens(float $width, float $height): array
    {
        return [
            'width' => $width, 'height' => $height, 'radius' => round($width * 0.15, 1),
            'holes' => [[4.0, 4.0, 27.0, 19.5, 6.0]],
            'islands' => [[6.0, 6.0, 15.5, 15.5, 4.6]],
            'lenses' => [[13.75, 13.75, 5.4]],
            'flashes' => [[26.2, 9.4, 1.8]],
        ];
    }

    /**
     * Horizontal camera bar across the back (Google Pixel 6 and later).
     *
     * @return array<string, mixed>
     */
    private static function visor(float $width, float $height): array
    {
        $top = round($height * 0.1, 1);

        return [
            'width' => $width, 'height' => $height, 'radius' => round($width * 0.14, 1),
            'holes' => [[2.0, $top - 1.5, $width - 4, 17.0, 8.5]],
            'islands' => [[3.5, $top, $width - 7, 14.0, 7.0]],
            'lenses' => [[$width * 0.24, $top + 7, 4.8], [$width * 0.42, $top + 7, 4.8]],
            'flashes' => [[$width * 0.58, $top + 7, 1.8]],
        ];
    }

    /**
     * Separate lens rings in a vertical column top-left (Galaxy S/A, Xperia), flash beside the top lens.
     *
     * @return array<string, mixed>
     */
    private static function lensColumn(float $width, float $height, int $count, float $ring): array
    {
        $x = 7.0 + $ring;
        $gap = $ring * 2.35;
        $lenses = array_map(fn (int $index): array => [$x, 7.0 + $ring + $index * $gap, $ring], range(0, $count - 1));
        $flash = [$x + $ring * 2.2, 7.0 + $ring, 1.8];

        return [
            'width' => $width, 'height' => $height, 'radius' => round($width * 0.13, 1),
            'holes' => [[$x - $ring - 1.4, 5.6, $ring * 2 + 2.8, ($count - 1) * $gap + $ring * 2 + 2.8, $ring + 1.4]],
            'roundHoles' => [[$flash[0], $flash[1], 2.9]],
            'lenses' => $lenses,
            'flashes' => [$flash],
        ];
    }

    /**
     * Square camera island in the top-left corner, scaled to the case size.
     *
     * @return array<string, mixed>
     */
    private static function generic(float $width, float $height): array
    {
        $size = round($width * 0.46, 1);
        $lens = round($size * 0.19, 1);
        $start = round($width * 0.07, 1);

        return [
            'width' => $width, 'height' => $height, 'radius' => round($width * 0.15, 1), 'body' => '#3a3a3c',
            'holes' => [[$start - 1.6, $start - 1.6, $size + 3.2, $size + 3.2, $size * 0.28]],
            'islands' => [[$start, $start, $size, $size, $size * 0.24]],
            'lenses' => [
                [$start + $size * 0.29, $start + $size * 0.29, $lens],
                [$start + $size * 0.29, $start + $size * 0.71, $lens],
                [$start + $size * 0.71, $start + $size * 0.5, $lens],
            ],
            'flashes' => [[$start + $size * 0.75, $start + $size * 0.17, $lens * 0.36]],
        ];
    }
}
