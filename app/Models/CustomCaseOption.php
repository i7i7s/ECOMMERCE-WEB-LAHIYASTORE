<?php

namespace App\Models;

use App\Enums\Currency;
use Database\Factories\CustomCaseOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['phone_model_id', 'case_type_id', 'price_jpy', 'price_idr', 'cost_price_idr', 'is_available'])]
class CustomCaseOption extends Model
{
    /** @use HasFactory<CustomCaseOptionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_jpy' => 'integer',
            'price_idr' => 'integer',
            'cost_price_idr' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<PhoneModel, $this>
     */
    public function phoneModel(): BelongsTo
    {
        return $this->belongsTo(PhoneModel::class);
    }

    /**
     * @return BelongsTo<CaseType, $this>
     */
    public function caseType(): BelongsTo
    {
        return $this->belongsTo(CaseType::class);
    }

    public function priceIn(Currency $currency): int
    {
        return $currency === Currency::Jpy ? $this->price_jpy : $this->price_idr;
    }
}
