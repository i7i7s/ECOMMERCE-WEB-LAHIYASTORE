<?php

namespace App\Models;

use App\Enums\CashFlow;
use App\Enums\CategoryScope;
use Database\Factories\TransactionCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'type', 'scope', 'is_system'])]
class TransactionCategory extends Model
{
    /** @use HasFactory<TransactionCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => CashFlow::class,
            'scope' => CategoryScope::class,
            'is_system' => 'boolean',
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
     * Built-in category by slug, e.g. "penjualan" or "refund" (PRD §11.3).
     */
    public static function system(string $slug): self
    {
        return static::query()->where('slug', $slug)->where('is_system', true)->firstOrFail();
    }
}
