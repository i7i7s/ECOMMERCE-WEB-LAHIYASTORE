<?php

namespace App\Console\Commands;

use App\Actions\Batches\CloseBatch;
use App\Actions\Batches\EnsureOpenBatch;
use App\Models\PreorderBatch;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('batches:close-due')]
#[Description('Close the open batch once its cutoff has passed and open the next one (PRD §6.1)')]
class CloseDueBatches extends Command
{
    public function handle(CloseBatch $closeBatch, EnsureOpenBatch $ensureOpenBatch): int
    {
        PreorderBatch::open()
            ->where('cutoff_at', '<=', now())
            ->each(function (PreorderBatch $batch) use ($closeBatch): void {
                $next = $closeBatch->handle($batch);
                $this->info("Batch {$batch->code} ditutup, {$next->code} dibuka.");
            });

        $ensureOpenBatch->handle();

        return self::SUCCESS;
    }
}
