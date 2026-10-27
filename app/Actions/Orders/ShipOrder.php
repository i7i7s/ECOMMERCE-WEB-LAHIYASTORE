<?php

namespace App\Actions\Orders;

use App\Actions\Finance\RecordExpense;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CashAccount;
use App\Models\Order;
use App\Models\TransactionCategory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Hands a packed order to the domestic courier (ORD-06, ORD-07). Every item must be
 * checked against the physical case first (PRD §6.10); the courier fee, when given,
 * is booked as a domestic shipping expense of the order's batch.
 */
class ShipOrder
{
    public function __construct(
        private TransitionOrderStatus $transition,
        private RecordExpense $recordExpense,
    ) {}

    /**
     * @param  array{courier: string, tracking_number: string, packing_photo_path?: string|null, shipping_cost?: int|null, cash_account_id?: int|null}  $shipping
     */
    public function handle(Order $order, User $packer, array $shipping): Order
    {
        if ($order->status !== OrderStatus::TibaDiJepang) {
            throw new BusinessRuleException('Hanya order yang sudah tiba di Jepang yang bisa dikirim.');
        }

        if ($order->items()->where('is_packed', false)->exists()) {
            throw new BusinessRuleException('Centang semua item yang sudah dicocokkan dengan casing fisiknya.');
        }

        if (blank($shipping['courier'] ?? null) || blank($shipping['tracking_number'] ?? null)) {
            throw new BusinessRuleException('Isi kurir dan nomor resi.');
        }

        $cost = (int) ($shipping['shipping_cost'] ?? 0);

        return DB::transaction(function () use ($order, $packer, $shipping, $cost): Order {
            if ($cost > 0) {
                $this->recordExpense->handle(
                    CashAccount::findOrFail($shipping['cash_account_id'] ?? null),
                    TransactionCategory::system('ongkir_domestik'),
                    $cost,
                    CarbonImmutable::now('Asia/Tokyo'),
                    $packer,
                    ['order_id' => $order->id, 'preorder_batch_id' => $order->preorder_batch_id, 'description' => "Ongkir domestik {$order->code}"],
                );
            }

            return $this->transition->handle($order, OrderStatus::Dikirim, $packer, "Dikirim via {$shipping['courier']} · resi {$shipping['tracking_number']}", [
                'domestic_courier' => $shipping['courier'],
                'tracking_number' => $shipping['tracking_number'],
                'packing_photo_path' => $shipping['packing_photo_path'] ?? null,
                'packed_by' => $packer->id,
                'shipped_at' => now(),
            ]);
        });
    }
}
