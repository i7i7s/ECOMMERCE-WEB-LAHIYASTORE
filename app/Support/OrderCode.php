<?php

namespace App\Support;

use App\Models\Order;
use Carbon\CarbonImmutable;

class OrderCode
{
    /**
     * Next order code for the Tokyo date, e.g. LS-20261001-001.
     */
    public static function next(?CarbonImmutable $now = null): string
    {
        $prefix = 'LS-'.($now ?? CarbonImmutable::now())->setTimezone(BatchSchedule::TIMEZONE)->format('Ymd').'-';

        $last = Order::query()->where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');
        $sequence = $last === null ? 1 : ((int) substr($last, strlen($prefix))) + 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
