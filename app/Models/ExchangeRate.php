<?php

namespace App\Models;

use App\Enums\ExchangeRateSource;
use Carbon\CarbonInterface;
use Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['effective_date', 'jpy_to_idr', 'source', 'set_by'])]
class ExchangeRate extends Model
{
    /** @use HasFactory<ExchangeRateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'jpy_to_idr' => 'decimal:4',
            'source' => ExchangeRateSource::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function setter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    /**
     * The rate in effect on the given date (the latest one on or before it).
     */
    public static function effectiveOn(?CarbonInterface $date = null): ?self
    {
        return static::query()
            ->whereDate('effective_date', '<=', ($date ?? now('Asia/Tokyo'))->toDateString())
            ->latest('effective_date')
            ->first();
    }
}
