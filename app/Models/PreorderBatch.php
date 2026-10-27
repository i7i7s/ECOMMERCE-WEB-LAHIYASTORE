<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Database\Factories\PreorderBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'status', 'cutoff_at', 'closed_at', 'produced_at', 'intl_courier', 'intl_tracking_number', 'shipped_at', 'eta_date', 'arrived_at', 'completed_at', 'note'])]
class PreorderBatch extends Model
{
    /** @use HasFactory<PreorderBatchFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => BatchStatus::class,
            'cutoff_at' => 'datetime',
            'closed_at' => 'datetime',
            'produced_at' => 'datetime',
            'shipped_at' => 'datetime',
            'eta_date' => 'date',
            'arrived_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<CashTransaction, $this>
     */
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', BatchStatus::Dibuka);
    }
}
