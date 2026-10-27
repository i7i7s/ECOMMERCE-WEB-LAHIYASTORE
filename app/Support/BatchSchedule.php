<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\CarbonImmutable;

/**
 * Preorder cutoff from store settings, in Tokyo time. Batches run monthly (cutoff on the last
 * day of the month) or weekly (cutoff on a chosen weekday); stores without the setting keep the
 * original weekly schedule, Sunday 23:59.
 */
class BatchSchedule
{
    public const TIMEZONE = 'Asia/Tokyo';

    public static function period(): string
    {
        return Setting::valueOf('batch_period', 'weekly') === 'monthly' ? 'monthly' : 'weekly';
    }

    /**
     * "bulanan" or "mingguan", for customer-facing text.
     */
    public static function periodLabel(): string
    {
        return self::period() === 'monthly' ? 'bulanan' : 'mingguan';
    }

    /**
     * The next cutoff at or after the given moment, in Tokyo time.
     */
    public static function nextCutoff(?CarbonImmutable $now = null): CarbonImmutable
    {
        $now = ($now ?? CarbonImmutable::now())->setTimezone(self::TIMEZONE);
        [$hour, $minute] = array_map('intval', explode(':', Setting::valueOf('batch_cutoff_time', '23:59')));

        if (self::period() === 'monthly') {
            $cutoff = $now->endOfMonth()->setTime($hour, $minute);

            return $cutoff->lt($now) ? $now->startOfMonth()->addMonth()->endOfMonth()->setTime($hour, $minute) : $cutoff;
        }

        $days = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $day = $days[Setting::valueOf('batch_cutoff_day', 'sunday')] ?? 0;
        $cutoff = $now->addDays(($day - $now->dayOfWeek + 7) % 7)->setTime($hour, $minute);

        return $cutoff->lt($now) ? $cutoff->addWeek() : $cutoff;
    }

    /**
     * Batch code for a cutoff: PO-2026-10 for a monthly batch, PO-2026-W40 (ISO week) for a weekly one.
     */
    public static function codeFor(CarbonImmutable $cutoff): string
    {
        $cutoff = $cutoff->setTimezone(self::TIMEZONE);

        if (self::period() === 'monthly') {
            return $cutoff->format('\P\O-Y-m');
        }

        return sprintf('PO-%d-W%02d', $cutoff->isoWeekYear(), $cutoff->isoWeek());
    }
}
