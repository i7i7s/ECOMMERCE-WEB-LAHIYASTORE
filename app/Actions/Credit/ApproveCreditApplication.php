<?php

namespace App\Actions\Credit;

use App\Enums\CreditApplicationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CreditApplication;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Admin approves a credit application and the payment schedule is created:
 * the down payment is due on the start date, then one installment per month.
 * Without a down payment the phone is handed over right away (status Berjalan).
 */
class ApproveCreditApplication
{
    public function handle(CreditApplication $application, User $admin, ?CarbonImmutable $startDate = null, ?string $note = null): CreditApplication
    {
        if (! $admin->isAdmin() || ! $admin->is_active) {
            throw new BusinessRuleException('Hanya admin yang bisa menyetujui kredit.');
        }

        if ($application->status !== CreditApplicationStatus::Diajukan) {
            throw new BusinessRuleException('Pengajuan ini sudah diproses.');
        }

        if (! $application->user->canBuyOnCredit()) {
            throw new BusinessRuleException('Verifikasi kredit pelanggan ini sudah dicabut. Verifikasi ulang dulu atau tolak pengajuannya.');
        }

        $start = ($startDate ?? CarbonImmutable::now('Asia/Tokyo'))->startOfDay();

        return DB::transaction(function () use ($application, $admin, $start, $note): CreditApplication {
            if ($application->down_payment > 0) {
                $application->installments()->create(['sequence' => 0, 'due_date' => $start->toDateString(), 'amount' => $application->down_payment]);
            }

            for ($month = 1; $month <= $application->tenor_months; $month++) {
                $application->installments()->create([
                    'sequence' => $month,
                    'due_date' => $start->addMonthsNoOverflow($month)->toDateString(),
                    'amount' => $application->monthly_installment,
                ]);
            }

            $application->update([
                'status' => $application->down_payment > 0 ? CreditApplicationStatus::Disetujui : CreditApplicationStatus::Berjalan,
                'decided_by' => $admin->id,
                'decided_at' => now(),
                'admin_note' => $note,
            ]);

            return $application;
        });
    }
}
