<?php

namespace App\Actions\Finance;

use App\Enums\CashFlow;
use App\Exceptions\BusinessRuleException;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\ExchangeRate;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Support\BaseCurrency;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Moving money between accounts, e.g. exchanging Yen into Rupiah to pay the vendor (FIN-05).
 * Recorded as two paired transactions with their actual amounts; transfers are not income
 * or expense, the difference in base currency is the exchange gain/loss (PRD §6.7).
 */
class TransferBetweenAccounts
{
    /**
     * @return array{0: CashTransaction, 1: CashTransaction} [outgoing, incoming]
     */
    public function handle(CashAccount $from, CashAccount $to, int $amountOut, int $amountIn, CarbonImmutable $date, User $by, ?string $description = null): array
    {
        if ($from->is($to)) {
            throw new BusinessRuleException('Akun asal dan tujuan harus berbeda.');
        }

        if ($amountOut <= 0 || $amountIn <= 0) {
            throw new BusinessRuleException('Jumlah keluar dan masuk harus lebih dari 0.');
        }

        if ($from->currency === $to->currency && $amountOut !== $amountIn) {
            throw new BusinessRuleException('Transfer dalam mata uang yang sama harus berjumlah sama.');
        }

        $rate = ExchangeRate::effectiveOn($date) ?? throw new BusinessRuleException('Kurs untuk tanggal ini belum diatur.');
        $group = (string) Str::uuid();
        $note = $description ?: "Transfer {$from->name} → {$to->name}";

        return DB::transaction(fn (): array => [
            $from->cashTransactions()->create([
                'transaction_category_id' => TransactionCategory::system('transfer_keluar')->id,
                'type' => CashFlow::Keluar,
                'currency' => $from->currency,
                'amount' => $amountOut,
                'exchange_rate' => $rate->jpy_to_idr,
                'amount_base' => BaseCurrency::convert($amountOut, $from->currency, (float) $rate->jpy_to_idr),
                'transaction_date' => $date->toDateString(),
                'transfer_group' => $group,
                'description' => $note,
                'recorded_by' => $by->id,
            ]),
            $to->cashTransactions()->create([
                'transaction_category_id' => TransactionCategory::system('transfer_masuk')->id,
                'type' => CashFlow::Masuk,
                'currency' => $to->currency,
                'amount' => $amountIn,
                'exchange_rate' => $rate->jpy_to_idr,
                'amount_base' => BaseCurrency::convert($amountIn, $to->currency, (float) $rate->jpy_to_idr),
                'transaction_date' => $date->toDateString(),
                'transfer_group' => $group,
                'description' => $note,
                'recorded_by' => $by->id,
            ]),
        ]);
    }
}
