<?php

namespace App\Actions\Finance;

use App\Exceptions\BusinessRuleException;
use App\Models\CashTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cash entries are never edited or deleted, only voided with a reason (FIN-07).
 * Voiding one side of a transfer voids both. Sales and refunds come from orders and
 * are corrected by cancelling the order, not here.
 */
class VoidCashTransaction
{
    public function handle(CashTransaction $transaction, User $admin, string $reason): void
    {
        if (! $admin->isAdmin()) {
            throw new BusinessRuleException('Hanya admin yang bisa membatalkan transaksi kas.');
        }

        if ($transaction->voided_at !== null) {
            throw new BusinessRuleException('Transaksi ini sudah di-void.');
        }

        if ($transaction->payment_id !== null || in_array($transaction->category->slug, ['penjualan', 'refund'], true)) {
            throw new BusinessRuleException('Pemasukan penjualan dan refund dikoreksi lewat order, bukan di buku kas.');
        }

        if ($transaction->category->slug === 'kredit_hp') {
            throw new BusinessRuleException('Pembayaran kredit dikoreksi lewat menu Kredit (batalkan pembayaran cicilannya), bukan di buku kas.');
        }

        if (blank($reason)) {
            throw new BusinessRuleException('Tulis alasan void.');
        }

        DB::transaction(function () use ($transaction, $admin, $reason): void {
            CashTransaction::query()
                ->when(
                    $transaction->transfer_group,
                    fn ($query) => $query->where('transfer_group', $transaction->transfer_group),
                    fn ($query) => $query->whereKey($transaction->id),
                )
                ->update(['voided_at' => now(), 'voided_by' => $admin->id, 'void_reason' => $reason]);
        });
    }
}
