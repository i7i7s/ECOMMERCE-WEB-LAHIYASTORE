<?php

namespace App\Actions\Finance;

use App\Enums\CashFlow;
use App\Enums\CategoryScope;
use App\Exceptions\BusinessRuleException;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\ExchangeRate;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Support\BaseCurrency;
use Carbon\CarbonImmutable;

/**
 * Money going out for an expense (FIN-04): vendor purchase, shipping, customs,
 * packaging, ... optionally tied to a batch, supplier and/or order.
 */
class RecordExpense
{
    /**
     * @param  array{preorder_batch_id?: int|null, supplier_id?: int|null, order_id?: int|null, description?: string|null, attachment_path?: string|null}  $links
     */
    public function handle(CashAccount $account, TransactionCategory $category, int $amount, CarbonImmutable $date, User $by, array $links = []): CashTransaction
    {
        if ($category->type !== CashFlow::Keluar || $category->scope === CategoryScope::Transfer || in_array($category->slug, ['refund'], true)) {
            throw new BusinessRuleException("Kategori {$category->name} bukan kategori pengeluaran.");
        }

        if ($amount <= 0) {
            throw new BusinessRuleException('Jumlah harus lebih dari 0.');
        }

        if (! $account->is_active) {
            throw new BusinessRuleException("Akun {$account->name} sudah nonaktif.");
        }

        $rate = ExchangeRate::effectiveOn($date) ?? throw new BusinessRuleException('Kurs untuk tanggal ini belum diatur.');

        return $account->cashTransactions()->create([
            'transaction_category_id' => $category->id,
            'type' => CashFlow::Keluar,
            'currency' => $account->currency,
            'amount' => $amount,
            'exchange_rate' => $rate->jpy_to_idr,
            'amount_base' => BaseCurrency::convert($amount, $account->currency, (float) $rate->jpy_to_idr),
            'transaction_date' => $date->toDateString(),
            'preorder_batch_id' => $links['preorder_batch_id'] ?? null,
            'supplier_id' => $links['supplier_id'] ?? null,
            'order_id' => $links['order_id'] ?? null,
            'description' => $links['description'] ?? null,
            'attachment_path' => $links['attachment_path'] ?? null,
            'recorded_by' => $by->id,
        ]);
    }
}
