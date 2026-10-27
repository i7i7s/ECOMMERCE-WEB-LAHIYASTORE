<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['order_id', 'cash_account_id', 'currency', 'amount', 'method', 'gateway', 'gateway_ref', 'proof_path', 'status', 'reject_reason', 'verified_by', 'verified_at'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'amount' => 'integer',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return HasOne<CashTransaction, $this>
     */
    public function cashTransaction(): HasOne
    {
        return $this->hasOne(CashTransaction::class);
    }
}
