<?php

namespace App\Support;

use App\Models\CreditApplication;
use Carbon\CarbonImmutable;

class CreditCode
{
    /**
     * Next credit application code for the Tokyo date, e.g. KR-20261003-001.
     */
    public static function next(?CarbonImmutable $now = null): string
    {
        $prefix = 'KR-'.($now ?? CarbonImmutable::now())->setTimezone(BatchSchedule::TIMEZONE)->format('Ymd').'-';

        $last = CreditApplication::query()->where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');
        $sequence = $last === null ? 1 : ((int) substr($last, strlen($prefix))) + 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
