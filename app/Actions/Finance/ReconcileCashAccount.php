<?php

namespace App\Actions\Finance;

use App\Models\CashAccount;
use App\Models\CashReconciliation;
use App\Models\User;

/**
 * Compares the real balance (bank app, cash count) with the cash book and keeps the
 * difference on record so gaps can be traced (FIN-09).
 */
class ReconcileCashAccount
{
    public function handle(CashAccount $account, int $actualBalance, User $by, ?string $note = null): CashReconciliation
    {
        $systemBalance = $account->balance();

        return $account->reconciliations()->create([
            'reconciled_on' => now('Asia/Tokyo')->toDateString(),
            'system_balance' => $systemBalance,
            'actual_balance' => $actualBalance,
            'difference' => $actualBalance - $systemBalance,
            'note' => $note,
            'user_id' => $by->id,
        ]);
    }
}
