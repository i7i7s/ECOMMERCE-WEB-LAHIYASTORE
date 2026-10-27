<?php

namespace App\Models;

use App\Enums\PhoneModelSource;
use Database\Factories\PhoneModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['phone_brand_id', 'name', 'slug', 'source', 'external_id', 'mockup_path', 'camera_mask_path', 'print_width_mm', 'print_height_mm', 'model_3d_path', 'is_active', 'is_featured', 'sort_order', 'synced_at'])]
class PhoneModel extends Model
{
    /** @use HasFactory<PhoneModelFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'source' => PhoneModelSource::class,
            'print_width_mm' => 'float',
            'print_height_mm' => 'float',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PhoneBrand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(PhoneBrand::class, 'phone_brand_id');
    }

    /**
     * @return HasMany<CustomCaseOption, $this>
     */
    public function customCaseOptions(): HasMany
    {
        return $this->hasMany(CustomCaseOption::class);
    }

    /**
     * A model gets the 2D editor only once a mockup and print size are set (PRD §6.8).
     *
     * @return Attribute<bool, never>
     */
    protected function hasPreview(): Attribute
    {
        return Attribute::get(fn (): bool => $this->mockup_path !== null && $this->print_width_mm !== null && $this->print_height_mm !== null);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
