<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;

/**
 * Staff checked with the vendor that the blank case for a model without mockup is
 * available, so the customer may now pay (CUST-14).
 */
class ConfirmOrderAvailability
{
    public function __construct(private TransitionOrderStatus $transition) {}

    public function handle(Order $order, User $by): Order
    {
        if ($order->status !== OrderStatus::MenungguKonfirmasi) {
            throw new BusinessRuleException('Order ini tidak sedang menunggu konfirmasi.');
        }

        return $this->transition->handle($order, OrderStatus::MenungguPembayaran, $by, 'Blank case tersedia di vendor', [
            'confirmed_at' => now(),
            'payment_due_at' => now()->addHours((int) Setting::valueOf('payment_window_hours', '24')),
        ]);
    }
}
