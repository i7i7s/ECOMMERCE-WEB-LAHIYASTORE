<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;

/**
 * Moves an order to another status along the PRD §16.2 diagram and records who did it.
 */
class TransitionOrderStatus
{
    /**
     * @param  array<string, mixed>  $attributes  Extra columns to update together with the status.
     */
    public function handle(Order $order, OrderStatus $to, ?User $by = null, ?string $note = null, array $attributes = []): Order
    {
        $from = $order->status;

        if (! $from->canTransitionTo($to)) {
            throw new BusinessRuleException("Order {$order->code} tidak bisa diubah dari {$from->getLabel()} ke {$to->getLabel()}.");
        }

        $order->fill([...$attributes, 'status' => $to])->save();

        $order->statusHistories()->create([
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'user_id' => $by?->id,
            'created_at' => now(),
        ]);

        return $order;
    }
}
