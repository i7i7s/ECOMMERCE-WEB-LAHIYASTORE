<?php

namespace App\Actions\Batches;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;

/**
 * Moves a paid order whose design is not final yet out of a closed batch into the
 * open one, so the closed batch can go to production (BATCH-07).
 */
class MoveOrderToOpenBatch
{
    public function __construct(private EnsureOpenBatch $ensureOpenBatch) {}

    public function handle(Order $order, User $admin): Order
    {
        if (! $admin->isAdmin()) {
            throw new BusinessRuleException('Hanya admin yang bisa memindah order antar batch.');
        }

        if ($order->status !== OrderStatus::Dibayar || $order->batch?->status !== BatchStatus::Ditutup) {
            throw new BusinessRuleException('Hanya order dibayar di batch yang sudah ditutup yang bisa dipindah.');
        }

        $from = $order->batch;
        $to = $this->ensureOpenBatch->handle();

        $order->update(['preorder_batch_id' => $to->id]);
        $order->statusHistories()->create([
            'from_status' => $order->status,
            'to_status' => $order->status,
            'note' => "Dipindah dari batch {$from->code} ke {$to->code}",
            'user_id' => $admin->id,
            'created_at' => now(),
        ]);

        return $order;
    }
}
