<?php

namespace App\Models;

use Database\Factories\CashReconciliationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cash_account_id', 'reconciled_on', 'system_balance', 'actual_balance', 'difference', 'note', 'user_id'])]
class CashReconciliation extends Model
{
    /** @use HasFactory<CashReconciliationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'reconciled_on' => 'date',
            'system_balance' => 'integer',
            'actual_balance' => 'integer',
            'difference' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CashAccount, $this>
     */
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
