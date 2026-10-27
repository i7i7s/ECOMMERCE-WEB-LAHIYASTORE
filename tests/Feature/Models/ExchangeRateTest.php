<?php

namespace Tests\Feature\Models;

use App\Models\ExchangeRate;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExchangeRateTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_uses_latest_rate_set_on_or_before_the_date(): void
    {
        ExchangeRate::factory()->create(['effective_date' => '2026-09-21', 'jpy_to_idr' => 104]);
        ExchangeRate::factory()->create(['effective_date' => '2026-09-28', 'jpy_to_idr' => 105.5]);
        ExchangeRate::factory()->create(['effective_date' => '2026-10-05', 'jpy_to_idr' => 106]);

        $this->assertSame('105.5000', ExchangeRate::effectiveOn(Carbon::parse('2026-10-01'))->jpy_to_idr);
        $this->assertSame('105.5000', ExchangeRate::effectiveOn(Carbon::parse('2026-09-28'))->jpy_to_idr);
    }

    public function test_returns_null_before_any_rate_was_set(): void
    {
        ExchangeRate::factory()->create(['effective_date' => '2026-09-28']);

        $this->assertNull(ExchangeRate::effectiveOn(Carbon::parse('2026-09-01')));
    }
}
