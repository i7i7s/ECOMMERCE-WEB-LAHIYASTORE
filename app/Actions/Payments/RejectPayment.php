<?php

namespace App\Actions\Payments;

use App\Actions\Orders\TransitionOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Admin rejects a transfer proof; the customer gets a new payment window to upload again (CART-04).
 */
class RejectPayment
{
    public function __construct(private TransitionOrderStatus $transition) {}

    public function handle(Payment $payment, User $admin, string $reason): Payment
    {
        if (! $admin->isAdmin()) {
            throw new BusinessRuleException('Hanya admin yang bisa memverifikasi pembayaran.');
        }

        if ($payment->status !== PaymentStatus::Menunggu) {
            throw new BusinessRuleException('Pembayaran ini sudah diproses.');
        }

        return DB::transaction(function () use ($payment, $admin, $reason): Payment {
            $payment->update([
                'status' => PaymentStatus::Ditolak,
                'reject_reason' => $reason,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            $this->transition->handle($payment->order, OrderStatus::MenungguPembayaran, $admin, "Bukti ditolak: {$reason}", [
                'payment_due_at' => now()->addHours((int) Setting::valueOf('payment_window_hours', '24')),
            ]);

            return $payment;
        });
    }
}
