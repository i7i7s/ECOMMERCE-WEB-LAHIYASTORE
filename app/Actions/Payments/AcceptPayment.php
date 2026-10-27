<?php

namespace App\Actions\Payments;

use App\Actions\Batches\EnsureOpenBatch;
use App\Actions\Orders\TransitionOrderStatus;
use App\Enums\CashFlow;
use App\Enums\DesignReviewStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Payment;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Support\BaseCurrency;
use App\Support\OperationalAlerts;
use Illuminate\Support\Facades\DB;

/**
 * Admin accepts a payment: the income is booked to the cash account and the order
 * joins the open batch, all in one transaction (PRD §6.3, §6.7, §9).
 */
class AcceptPayment
{
    public function __construct(
        private TransitionOrderStatus $transition,
        private EnsureOpenBatch $ensureOpenBatch,
    ) {}

    public function handle(Payment $payment, User $admin): Payment
    {
        if (! $admin->isAdmin()) {
            throw new BusinessRuleException('Hanya admin yang bisa memverifikasi pembayaran.');
        }

        if ($payment->status !== PaymentStatus::Menunggu) {
            throw new BusinessRuleException('Pembayaran ini sudah diproses.');
        }

        return DB::transaction(function () use ($payment, $admin): Payment {
            $order = $payment->order()->lockForUpdate()->firstOrFail();

            $payment->update([
                'status' => PaymentStatus::Diterima,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            $payment->cashTransaction()->create([
                'cash_account_id' => $payment->cash_account_id,
                'transaction_category_id' => TransactionCategory::system('penjualan')->id,
                'type' => CashFlow::Masuk,
                'currency' => $payment->currency,
                'amount' => $payment->amount,
                'exchange_rate' => $order->exchange_rate,
                'amount_base' => BaseCurrency::convert($payment->amount, $payment->currency, (float) $order->exchange_rate),
                'transaction_date' => now('Asia/Tokyo')->toDateString(),
                'order_id' => $order->id,
                'description' => "Penjualan {$order->code}",
                'recorded_by' => $admin->id,
            ]);

            $batch = $this->ensureOpenBatch->handle();

            $this->transition->handle($order, OrderStatus::Dibayar, $admin, "Pembayaran diterima, masuk batch {$batch->code}", [
                'paid_at' => now(),
                'payment_due_at' => null,
                'preorder_batch_id' => $batch->id,
            ]);

            if ($order->items()->whereHas('design', fn ($query) => $query->where('review_status', DesignReviewStatus::Menunggu))->exists()) {
                OperationalAlerts::send('Desain perlu review', "{$order->code} sudah dibayar dan desainnya perlu direview.", route('filament.admin.resources.orders.view', ['record' => $order->id]));
            }

            return $payment;
        });
    }
}
