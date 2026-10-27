<?php

namespace App\Models;

use App\Enums\DesignReviewStatus;
use Database\Factories\OrderItemDesignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_item_id', 'phone_model_id', 'phone_model_text', 'case_type_id', 'case_color_id', 'has_preview', 'position_note',
    'original_path', 'preview_path', 'print_path', 'image_width_px', 'image_height_px', 'pos_x', 'pos_y', 'scale', 'rotation',
    'rights_agreed_at', 'review_status', 'review_note', 'reviewed_by', 'reviewed_at',
])]
class OrderItemDesign extends Model
{
    /** @use HasFactory<OrderItemDesignFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'has_preview' => 'boolean',
            'image_width_px' => 'integer',
            'image_height_px' => 'integer',
            'pos_x' => 'float',
            'pos_y' => 'float',
            'scale' => 'float',
            'rotation' => 'float',
            'rights_agreed_at' => 'datetime',
            'review_status' => DesignReviewStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
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

    /**
     * @return BelongsTo<CaseColor, $this>
     */
    public function caseColor(): BelongsTo
    {
        return $this->belongsTo(CaseColor::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
