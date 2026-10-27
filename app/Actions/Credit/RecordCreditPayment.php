<?php

namespace App\Actions\Credit;

use App\Enums\CashFlow;
use App\Enums\CreditApplicationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CashAccount;
use App\Models\CreditInstallment;
use App\Models\ExchangeRate;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Support\BaseCurrency;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Admin records a received down payment or installment. The income is booked in the cash book
 * (category Kredit HP) in the same transaction. Paying the down payment starts the credit
 * (phone handed over); paying the last row closes it as Lunas.
 */
class RecordCreditPayment
{
    public function handle(CreditInstallment $installment, CashAccount $account, CarbonImmutable $paidOn, User $admin): CreditInstallment
    {
        if (! $admin->isAdmin() || ! $admin->is_active) {
            throw new BusinessRuleException('Hanya admin yang bisa mencatat pembayaran kredit.');
        }

        $application = $installment->application;

        if (! in_array($application->status, [CreditApplicationStatus::Disetujui, CreditApplicationStatus::Berjalan], true)) {
            throw new BusinessRuleException('Kredit ini tidak sedang berjalan.');
        }

        if ($installment->isPaid()) {
            throw new BusinessRuleException("{$installment->label()} sudah dibayar.");
        }

        $earlier = $application->installments()->whereNull('paid_at')->where('sequence', '<', $installment->sequence)->first();

        if ($earlier !== null) {
            throw new BusinessRuleException("Catat {$earlier->label()} dulu.");
        }

        if ($account->currency !== $application->currency || ! $account->is_active) {
            throw new BusinessRuleException("Pilih akun kas {$application->currency->value} yang aktif.");
        }

        if ($paidOn->isAfter(CarbonImmutable::now('Asia/Tokyo')->endOfDay())) {
            throw new BusinessRuleException('Tanggal bayar tidak boleh di masa depan.');
        }

        $rate = ExchangeRate::effectiveOn($paidOn) ?? throw new BusinessRuleException('Kurs untuk tanggal ini belum diatur.');

        return DB::transaction(function () use ($installment, $application, $account, $paidOn, $admin, $rate): CreditInstallment {
            $transaction = $account->cashTransactions()->create([
                'transaction_category_id' => TransactionCategory::system('kredit_hp')->id,
                'type' => CashFlow::Masuk,
                'currency' => $application->currency,
                'amount' => $installment->amount,
                'exchange_rate' => $rate->jpy_to_idr,
                'amount_base' => BaseCurrency::convert($installment->amount, $application->currency, (float) $rate->jpy_to_idr),
                'transaction_date' => $paidOn->toDateString(),
                'description' => "Kredit {$application->code} · {$installment->label()} · {$application->customer_name}",
                'recorded_by' => $admin->id,
            ]);

            $installment->update([
                'paid_at' => $paidOn->setTime(12, 0),
                'cash_transaction_id' => $transaction->id,
                'recorded_by' => $admin->id,
            ]);

            $remaining = $application->installments()->whereNull('paid_at')->exists();

            $application->update(match (true) {
                ! $remaining => ['status' => CreditApplicationStatus::Lunas, 'completed_at' => now()],
                default => ['status' => CreditApplicationStatus::Berjalan],
            });

            return $installment;
        });
    }
}
