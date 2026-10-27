<?php

namespace App\Models;

use App\Enums\CashFlow;
use App\Enums\Currency;
use Database\Factories\CashTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cash book entry. Never edited or deleted, only voided with a reason (PRD §6.7).
 */
#[Fillable([
    'cash_account_id', 'transaction_category_id', 'type', 'currency', 'amount', 'exchange_rate', 'amount_base', 'transaction_date',
    'order_id', 'payment_id', 'preorder_batch_id', 'supplier_id', 'transfer_group', 'description', 'attachment_path', 'recorded_by',
    'voided_at', 'voided_by', 'void_reason',
])]
class CashTransaction extends Model
{
    /** @use HasFactory<CashTransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => CashFlow::class,
            'currency' => Currency::class,
            'amount' => 'integer',
            'exchange_rate' => 'decimal:4',
            'amount_base' => 'integer',
            'transaction_date' => 'date',
            'voided_at' => 'datetime',
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
     * @return BelongsTo<TransactionCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<PreorderBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(PreorderBatch::class, 'preorder_batch_id');
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    #[Scope]
    protected function notVoided(Builder $query): void
    {
        $query->whereNull('voided_at');
    }
}
