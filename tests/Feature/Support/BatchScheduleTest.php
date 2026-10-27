<?php

namespace Tests\Feature\Support;

use App\Models\Setting;
use App\Support\BatchSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BatchScheduleTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function weekly(): void
    {
        Setting::updateOrCreate(['key' => 'batch_period'], ['value' => 'weekly']);
    }

    public function test_monthly_cutoff_is_the_last_day_of_the_month_at_2359_tokyo_time(): void
    {
        $cutoff = BatchSchedule::nextCutoff(CarbonImmutable::parse('2026-10-01 05:00', 'UTC'));

        $this->assertSame('2026-10-31 23:59', $cutoff->format('Y-m-d H:i'));
        $this->assertSame('PO-2026-10', BatchSchedule::codeFor($cutoff));
        $this->assertSame('bulanan', BatchSchedule::periodLabel());
    }

    public function test_monthly_cutoff_rolls_to_the_next_month_after_the_last_day(): void
    {
        // 31 Oct 15:30 UTC is already 1 Nov 00:30 in Tokyo.
        $cutoff = BatchSchedule::nextCutoff(CarbonImmutable::parse('2026-10-31 15:30', 'UTC'));

        $this->assertSame('2026-11-30 23:59', $cutoff->format('Y-m-d H:i'));
        $this->assertSame('PO-2026-11', BatchSchedule::codeFor($cutoff));
    }

    public function test_next_cutoff_is_the_coming_sunday_at_2359_tokyo_time(): void
    {
        $this->weekly();
        $cutoff = BatchSchedule::nextCutoff(CarbonImmutable::parse('2026-10-01 05:00', 'UTC'));

        $this->assertSame('2026-10-04 23:59', $cutoff->format('Y-m-d H:i'));
        $this->assertSame('Asia/Tokyo', $cutoff->timezoneName);
        $this->assertSame('PO-2026-W40', BatchSchedule::codeFor($cutoff));
    }

    public function test_cutoff_rolls_to_next_week_once_monday_starts_in_tokyo(): void
    {
        $this->weekly();
        // Sunday 15:30 UTC is already Monday 00:30 in Tokyo.
        $cutoff = BatchSchedule::nextCutoff(CarbonImmutable::parse('2026-10-04 15:30', 'UTC'));

        $this->assertSame('2026-10-11 23:59', $cutoff->format('Y-m-d H:i'));
        $this->assertSame('PO-2026-W41', BatchSchedule::codeFor($cutoff));
    }

    public function test_code_uses_iso_week_year_at_year_end(): void
    {
        $this->weekly();
        $this->assertSame('PO-2026-W53', BatchSchedule::codeFor(CarbonImmutable::parse('2027-01-03 23:59', 'Asia/Tokyo')));
    }
}
