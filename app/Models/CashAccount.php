<?php

namespace App\Models;

use App\Enums\CashAccountType;
use App\Enums\CashFlow;
use App\Enums\Currency;
use Database\Factories\CashAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type', 'currency', 'bank_name', 'account_number', 'account_holder', 'show_at_checkout', 'is_active'])]
class CashAccount extends Model
{
    /** @use HasFactory<CashAccountFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => CashAccountType::class,
            'currency' => Currency::class,
            'show_at_checkout' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CashTransaction, $this>
     */
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    /**
     * @return HasMany<CashReconciliation, $this>
     */
    public function reconciliations(): HasMany
    {
        return $this->hasMany(CashReconciliation::class);
    }

    /**
     * Balance in the account's own currency, computed from non-voided transactions (PRD §11.2 #7).
     */
    public function balance(): int
    {
        $totals = $this->cashTransactions()
            ->whereNull('voided_at')
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return (int) ($totals[CashFlow::Masuk->value] ?? 0) - (int) ($totals[CashFlow::Keluar->value] ?? 0);
    }
}
