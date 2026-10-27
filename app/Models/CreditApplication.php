<?php

namespace App\Models;

use App\Enums\CreditApplicationStatus;
use App\Enums\Currency;
use Database\Factories\CreditApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A phone bought on credit, in yen or rupiah. Amounts are in the credit's currency and snapshot
 * the terms at submission.
 */
#[Fillable([
    'code', 'user_id', 'product_variant_id', 'item_name', 'currency', 'exchange_rate', 'customer_name', 'customer_phone', 'customer_email',
    'cash_price', 'down_payment', 'tenor_months', 'margin_percent', 'admin_fee', 'monthly_installment', 'total_payable',
    'status', 'customer_note', 'admin_note', 'decided_by', 'decided_at', 'completed_at',
])]
class CreditApplication extends Model
{
    /** @use HasFactory<CreditApplicationFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'diajukan',
        'admin_fee' => 0,
        'currency' => 'JPY',
    ];

    protected function casts(): array
    {
        return [
            'status' => CreditApplicationStatus::class,
            'currency' => Currency::class,
            'exchange_rate' => 'decimal:4',
            'cash_price' => 'integer',
            'down_payment' => 'integer',
            'tenor_months' => 'integer',
            'margin_percent' => 'decimal:2',
            'admin_fee' => 'integer',
            'monthly_installment' => 'integer',
            'total_payable' => 'integer',
            'decided_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @return HasMany<CreditInstallment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(CreditInstallment::class)->orderBy('sequence');
    }

    public function financedAmount(): int
    {
        return $this->cash_price - $this->down_payment;
    }

    public function money(int $amount): string
    {
        return $this->currency->format($amount);
    }

    /**
     * Amount paid so far (down payment and installments), in the credit's currency.
     */
    public function paidAmount(): int
    {
        return (int) $this->installments()->whereNotNull('paid_at')->sum('amount');
    }

    public function nextDueInstallment(): ?CreditInstallment
    {
        return $this->installments()->whereNull('paid_at')->first();
    }
}
