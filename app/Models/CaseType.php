<?php

namespace App\Models;

use App\Enums\Currency;
use Database\Factories\CaseTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'default_price_jpy', 'default_price_idr', 'default_cost_price_idr', 'is_active', 'sort_order'])]
class CaseType extends Model
{
    /** @use HasFactory<CaseTypeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'default_price_jpy' => 'integer',
            'default_price_idr' => 'integer',
            'default_cost_price_idr' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CustomCaseOption, $this>
     */
    public function customCaseOptions(): HasMany
    {
        return $this->hasMany(CustomCaseOption::class);
    }

    /**
     * Default price, used for phone models without a mockup.
     */
    public function defaultPriceIn(Currency $currency): int
    {
        return $currency === Currency::Jpy ? $this->default_price_jpy : $this->default_price_idr;
    }
}
