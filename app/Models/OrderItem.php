<?php

namespace App\Models;

use App\Enums\OrderItemType;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['order_id', 'item_type', 'product_variant_id', 'custom_case_option_id', 'item_name', 'qty', 'unit_price', 'unit_cost_idr', 'subtotal', 'is_packed'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'item_type' => OrderItemType::class,
            'qty' => 'integer',
            'unit_price' => 'integer',
            'unit_cost_idr' => 'integer',
            'subtotal' => 'integer',
            'is_packed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * @return BelongsTo<CustomCaseOption, $this>
     */
    public function customCaseOption(): BelongsTo
    {
        return $this->belongsTo(CustomCaseOption::class);
    }

    /**
     * @return HasOne<OrderItemDesign, $this>
     */
    public function design(): HasOne
    {
        return $this->hasOne(OrderItemDesign::class);
    }
}
