<?php

namespace App\Models;

use Database\Factories\PhoneBrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'external_id', 'sort_order'])]
class PhoneBrand extends Model
{
    /** @use HasFactory<PhoneBrandFactory> */
    use HasFactory;

    /**
     * @return HasMany<PhoneModel, $this>
     */
    public function phoneModels(): HasMany
    {
        return $this->hasMany(PhoneModel::class);
    }
}
