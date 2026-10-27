<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;

/**
 * Marks a shipped order as received: by the admin, or by the system 7 days after shipping (PRD §6.3).
 */
class CompleteOrder
{
    public function __construct(private TransitionOrderStatus $transition) {}

    public function handle(Order $order, ?User $by = null): Order
    {
        if ($order->status !== OrderStatus::Dikirim) {
            throw new BusinessRuleException('Hanya order yang sudah dikirim yang bisa diselesaikan.');
        }

        return $this->transition->handle($order, OrderStatus::Selesai, $by, $by ? 'Diterima pelanggan' : 'Selesai otomatis setelah dikirim', [
            'completed_at' => now(),
        ]);
    }
}
