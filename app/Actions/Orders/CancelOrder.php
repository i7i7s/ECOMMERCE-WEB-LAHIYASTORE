<?php

namespace App\Actions\Orders;

use App\Enums\CashFlow;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Support\BaseCurrency;
use Illuminate\Support\Facades\DB;

/**
 * Cancels an order (PRD §6.11). A paid order that is not yet in production is refunded in
 * full from the account and currency it was paid with; produced orders cannot be cancelled.
 * A null $by means the system (e.g. payment window expired).
 */
class CancelOrder
{
    public function __construct(private TransitionOrderStatus $transition) {}

    public function handle(Order $order, ?User $by, string $reason): Order
    {
        if ($order->status === OrderStatus::Dibayar && ! $by?->isAdmin()) {
            throw new BusinessRuleException('Hanya admin yang bisa membatalkan order yang sudah dibayar.');
        }

        if (! $order->status->canTransitionTo(OrderStatus::Dibatalkan)) {
            throw new BusinessRuleException("Order berstatus {$order->status->getLabel()} tidak bisa dibatalkan.");
        }

        return DB::transaction(function () use ($order, $by, $reason): Order {
            if ($order->status === OrderStatus::Dibayar) {
                $this->refund($order, $by);
            }

            return $this->transition->handle($order, OrderStatus::Dibatalkan, $by, $reason, [
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'payment_due_at' => null,
            ]);
        });
    }

    private function refund(Order $order, User $admin): void
    {
        $payment = $order->payments()->where('status', PaymentStatus::Diterima)->latest('id')->firstOrFail();

        $payment->cashAccount->cashTransactions()->create([
            'transaction_category_id' => TransactionCategory::system('refund')->id,
            'type' => CashFlow::Keluar,
            'currency' => $payment->currency,
            'amount' => $payment->amount,
            'exchange_rate' => $order->exchange_rate,
            'amount_base' => BaseCurrency::convert($payment->amount, $payment->currency, (float) $order->exchange_rate),
            'transaction_date' => now('Asia/Tokyo')->toDateString(),
            'order_id' => $order->id,
            'description' => "Refund {$order->code}",
            'recorded_by' => $admin->id,
        ]);
    }
}
