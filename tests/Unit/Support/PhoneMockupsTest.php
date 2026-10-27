<?php

namespace Tests\Unit\Support;

use App\Support\PhoneMockups;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneMockupsTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function drawnModels(): array
    {
        return collect(PhoneMockups::slugs())->mapWithKeys(fn (string $slug): array => [$slug => [$slug]])->all();
    }

    #[DataProvider('drawnModels')]
    public function test_every_camera_part_lies_inside_a_case_cutout(string $slug): void
    {
        $mockup = PhoneMockups::for($slug);
        $parts = [...$mockup['lenses'], ...$mockup['periscopes'], ...$mockup['flashes'], ...$mockup['sensors'], ...$mockup['dots']];

        $this->assertNotEmpty($mockup['lenses']);

        foreach ($parts as [$cx, $cy, $r]) {
            $insideRect = collect($mockup['holes'])->contains(fn (array $hole): bool => $cx - $r >= $hole[0] && $cx + $r <= $hole[0] + $hole[2] && $cy - $r >= $hole[1] && $cy + $r <= $hole[1] + $hole[3]);
            $insideCircle = collect($mockup['roundHoles'])->contains(fn (array $hole): bool => hypot($cx - $hole[0], $cy - $hole[1]) + $r <= $hole[2]);

            $this->assertTrue($insideRect || $insideCircle, "Part at {$cx},{$cy} of {$slug} is covered by the case.");
            $this->assertLessThan($mockup['width'], $cx + $r);
            $this->assertLessThan($mockup['height'], $cy + $r);
        }
    }

    /**
     * @return array<string, array{string, float}>
     */
    public static function approximateModels(): array
    {
        return [
            'iPhone Pro' => ['iPhone 13 Pro Max', 77.5],
            'iPhone base' => ['iPhone 12', 73.5],
            'iPhone SE' => ['iPhone SE (3rd generation)', 70.5],
            'Pixel' => ['Google Pixel 8', 73.5],
            'Galaxy' => ['Samsung Galaxy A55', 73.5],
            'Galaxy Ultra' => ['Samsung Galaxy S24 Ultra', 77.5],
            'Xperia' => ['Sony Xperia 10 V', 73.5],
            'other brand' => ['Redmi Note 13 Pro+', 77.5],
        ];
    }

    #[DataProvider('approximateModels')]
    public function test_approximate_preview_keeps_every_camera_part_inside_a_cutout(string $name, float $width): void
    {
        $mockup = PhoneMockups::approximate($name);
        $parts = [...$mockup['lenses'], ...$mockup['flashes'], ...$mockup['sensors']];

        $this->assertSame($width, $mockup['width']);
        $this->assertNotEmpty($mockup['lenses']);

        foreach ($parts as [$cx, $cy, $r]) {
            $insideRect = collect($mockup['holes'])->contains(fn (array $hole): bool => $cx - $r >= $hole[0] && $cx + $r <= $hole[0] + $hole[2] && $cy - $r >= $hole[1] && $cy + $r <= $hole[1] + $hole[3]);
            $insideCircle = collect($mockup['roundHoles'])->contains(fn (array $hole): bool => hypot($cx - $hole[0], $cy - $hole[1]) + $r <= $hole[2]);

            $this->assertTrue($insideRect || $insideCircle, "Part at {$cx},{$cy} of {$name} is covered by the case.");
        }
    }

    public function test_unknown_model_gets_a_generic_camera_in_its_own_size(): void
    {
        $mockup = PhoneMockups::for('fairphone-5', 72.0, 150.0);

        $this->assertFalse(PhoneMockups::has('fairphone-5'));
        $this->assertSame(72.0, $mockup['width']);
        $this->assertSame(150.0, $mockup['height']);
        $this->assertCount(3, $mockup['lenses']);
    }
}
