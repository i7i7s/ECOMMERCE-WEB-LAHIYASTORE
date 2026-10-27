<?php

namespace App\Models;

use App\Enums\Currency;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'phone_model_id', 'sku', 'name', 'color_name', 'color_hex', 'storage_gb', 'price_jpy', 'price_idr', 'cost_price_idr', 'is_available'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_jpy' => 'integer',
            'price_idr' => 'integer',
            'cost_price_idr' => 'integer',
            'storage_gb' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<PhoneModel, $this>
     */
    public function phoneModel(): BelongsTo
    {
        return $this->belongsTo(PhoneModel::class);
    }

    /**
     * Storage as shown to customers: 256 GB, 1 TB.
     */
    public function storageLabel(): ?string
    {
        if ($this->storage_gb === null) {
            return null;
        }

        return $this->storage_gb >= 1024 ? intdiv($this->storage_gb, 1024).' TB' : $this->storage_gb.' GB';
    }

    public function priceIn(Currency $currency): int
    {
        return $currency === Currency::Jpy ? $this->price_jpy : $this->price_idr;
    }
}
