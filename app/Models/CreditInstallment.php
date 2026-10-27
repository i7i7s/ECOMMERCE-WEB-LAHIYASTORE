<?php

namespace App\Models;

use Database\Factories\CreditInstallmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row of a credit payment schedule. Sequence 0 is the down payment.
 */
#[Fillable(['credit_application_id', 'sequence', 'due_date', 'amount', 'paid_at', 'cash_transaction_id', 'recorded_by'])]
class CreditInstallment extends Model
{
    /** @use HasFactory<CreditInstallmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'due_date' => 'date',
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CreditApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(CreditApplication::class, 'credit_application_id');
    }

    /**
     * @return BelongsTo<CashTransaction, $this>
     */
    public function cashTransaction(): BelongsTo
    {
        return $this->belongsTo(CashTransaction::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isDownPayment(): bool
    {
        return $this->sequence === 0;
    }

    public function label(): string
    {
        return $this->isDownPayment() ? 'DP' : "Cicilan {$this->sequence}";
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function isOverdue(): bool
    {
        // Due dates are Tokyo calendar dates; compare them as dates, not instants.
        return ! $this->isPaid() && $this->due_date->toDateString() < now('Asia/Tokyo')->toDateString();
    }
}
