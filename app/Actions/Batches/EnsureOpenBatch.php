<?php

namespace App\Actions\Batches;

use App\Enums\BatchStatus;
use App\Models\PreorderBatch;
use App\Support\BatchSchedule;
use Carbon\CarbonImmutable;

/**
 * There is always exactly one open batch (PRD §6.1). Returns it, opening a new one when needed.
 */
class EnsureOpenBatch
{
    public function handle(): PreorderBatch
    {
        $open = PreorderBatch::open()->oldest('cutoff_at')->first();

        if ($open !== null) {
            return $open;
        }

        // Never reuse a period that already has a batch, e.g. when a batch is closed early by hand.
        $latestCutoff = PreorderBatch::query()->max('cutoff_at');
        $from = CarbonImmutable::now();

        if ($latestCutoff !== null && CarbonImmutable::parse($latestCutoff, 'UTC')->greaterThanOrEqualTo($from)) {
            $from = CarbonImmutable::parse($latestCutoff, 'UTC')->addMinute();
        }

        $cutoff = BatchSchedule::nextCutoff($from);

        while (PreorderBatch::where('code', BatchSchedule::codeFor($cutoff))->exists()) {
            $cutoff = BatchSchedule::nextCutoff($cutoff->addMinute());
        }

        return PreorderBatch::create([
            'code' => BatchSchedule::codeFor($cutoff),
            'status' => BatchStatus::Dibuka,
            'cutoff_at' => $cutoff->utc(),
        ]);
    }
}
