<?php

namespace App\Actions\Payments;

use App\Models\CashAccount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Payment received in chat for a manual WA/TikTok order (PRD §7.3). Orders entered by the
 * admin are accepted straight away; orders entered by staff wait for admin verification.
 */
class RecordManualPayment
{
    public function __construct(
        private SubmitPaymentProof $submitPaymentProof,
        private AcceptPayment $acceptPayment,
    ) {}

    public function handle(Order $order, CashAccount $account, UploadedFile|string $proof, User $recordedBy): Payment
    {
        $payment = $this->submitPaymentProof->handle($order, $proof, $account, $recordedBy);

        if ($recordedBy->isAdmin()) {
            $this->acceptPayment->handle($payment, $recordedBy);
        }

        return $payment->refresh();
    }
}
