<?php

namespace App\Models;

use App\Enums\ComplaintResolution;
use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use Database\Factories\OrderComplaintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'type', 'description', 'evidence_path', 'resolution', 'replacement_order_id', 'status', 'handled_by', 'photo_path', 'received_at', 'resolution_note', 'resolved_at'])]
class OrderComplaint extends Model
{
    /** @use HasFactory<OrderComplaintFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'resolved_at' => 'datetime',
            'type' => ComplaintType::class,
            'resolution' => ComplaintResolution::class,
            'status' => ComplaintStatus::class,
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
     * @return BelongsTo<Order, $this>
     */
    public function replacementOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'replacement_order_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
