<?php

namespace App\Actions\Batches;

use App\Enums\BatchStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\PreorderBatch;
use Illuminate\Support\Facades\DB;

/**
 * Closes the open batch at cutoff (scheduler) or by hand, and opens the next one (PRD §6.1).
 */
class CloseBatch
{
    public function __construct(private EnsureOpenBatch $ensureOpenBatch) {}

    /**
     * @return PreorderBatch The newly opened batch.
     */
    public function handle(PreorderBatch $batch): PreorderBatch
    {
        if ($batch->status !== BatchStatus::Dibuka) {
            throw new BusinessRuleException("Batch {$batch->code} sudah ditutup.");
        }

        return DB::transaction(function () use ($batch): PreorderBatch {
            $batch->update(['status' => BatchStatus::Ditutup, 'closed_at' => now()]);

            return $this->ensureOpenBatch->handle();
        });
    }
}
