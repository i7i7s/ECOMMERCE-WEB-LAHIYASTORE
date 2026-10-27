<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'user_id', 'preorder_batch_id', 'parent_order_id', 'source', 'currency', 'exchange_rate',
    'customer_name', 'customer_phone', 'postal_code', 'prefecture', 'city', 'address_line1', 'address_line2',
    'subtotal', 'total', 'status', 'domestic_courier', 'tracking_number', 'packing_photo_path', 'packed_by',
    'confirmed_at', 'payment_due_at', 'paid_at', 'shipped_at', 'completed_at', 'cancelled_at', 'cancel_reason', 'created_by',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'source' => OrderSource::class,
            'currency' => Currency::class,
            'status' => OrderStatus::class,
            'exchange_rate' => 'decimal:4',
            'subtotal' => 'integer',
            'total' => 'integer',
            'confirmed_at' => 'datetime',
            'payment_due_at' => 'datetime',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PreorderBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(PreorderBatch::class, 'preorder_batch_id');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function parentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_order_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function packer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packed_by');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    /**
     * @return HasMany<OrderComplaint, $this>
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(OrderComplaint::class);
    }

    /**
     * Last four digits of the customer's phone, used to look up an order publicly.
     */
    public function phoneSuffix(): string
    {
        return substr(preg_replace('/\D/', '', $this->customer_phone), -4);
    }

    public function formattedTotal(): string
    {
        return $this->currency->format($this->total);
    }
}
