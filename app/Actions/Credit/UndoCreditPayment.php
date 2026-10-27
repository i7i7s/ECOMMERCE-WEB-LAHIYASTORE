<?php

namespace App\Actions\Credit;

use App\Enums\CreditApplicationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CreditInstallment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Corrects a payment recorded by mistake: its cash book income is voided (never deleted)
 * and the row is unpaid again. Only the latest payment can be undone, so the schedule
 * always stays paid in order.
 */
class UndoCreditPayment
{
    public function handle(CreditInstallment $installment, User $admin, string $reason): CreditInstallment
    {
        if (! $admin->isAdmin() || ! $admin->is_active) {
            throw new BusinessRuleException('Hanya admin yang bisa membatalkan pembayaran kredit.');
        }

        if (! $installment->isPaid()) {
            throw new BusinessRuleException("{$installment->label()} belum dibayar.");
        }

        if (blank($reason)) {
            throw new BusinessRuleException('Tulis alasannya.');
        }

        $application = $installment->application;

        if ($application->installments()->whereNotNull('paid_at')->where('sequence', '>', $installment->sequence)->exists()) {
            throw new BusinessRuleException('Batalkan pembayaran yang paling akhir dulu.');
        }

        return DB::transaction(function () use ($installment, $application, $admin, $reason): CreditInstallment {
            $installment->cashTransaction?->update([
                'voided_at' => now(),
                'voided_by' => $admin->id,
                'void_reason' => "Pembayaran kredit dibatalkan: {$reason}",
            ]);

            $installment->update(['paid_at' => null, 'cash_transaction_id' => null, 'recorded_by' => null]);

            $nothingPaid = ! $application->installments()->whereNotNull('paid_at')->exists();
            $hasDownPayment = $application->installments()->where('sequence', 0)->exists();

            $application->update([
                'status' => $nothingPaid && $hasDownPayment ? CreditApplicationStatus::Disetujui : CreditApplicationStatus::Berjalan,
                'completed_at' => null,
            ]);

            return $installment;
        });
    }
}
