<?php

namespace Tests\Unit\Filament\Support;

use App\Filament\Support\PriceFields;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class PriceFieldsTest extends TestCase
{
    #[TestWith([2800, 105.0, 294000])]
    #[TestWith([2800, 105.2, 295000])]
    #[TestWith([999, 104.37, 105000])]
    #[TestWith([0, 105.0, 0])]
    public function test_rupiah_suggestion_rounds_up_to_nearest_thousand(int $jpy, float $rate, int $expected): void
    {
        $this->assertSame($expected, PriceFields::idrFromJpy($jpy, $rate));
    }
}
