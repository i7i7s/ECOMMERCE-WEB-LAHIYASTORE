<?php

namespace App\Actions\Orders;

use App\Actions\Batches\EnsureOpenBatch;
use App\Enums\CashFlow;
use App\Enums\ComplaintResolution;
use App\Enums\ComplaintStatus;
use App\Enums\DesignReviewStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CashTransaction;
use App\Models\Order;
use App\Models\OrderComplaint;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Support\BaseCurrency;
use App\Support\OrderCode;
use Illuminate\Support\Facades\DB;

class ResolveComplaint
{
    public function __construct(private EnsureOpenBatch $ensureOpenBatch) {}

    public function handle(OrderComplaint $complaint, User $admin, ComplaintResolution $resolution, string $note): OrderComplaint
    {
        abort_unless($admin->is_active && $admin->isAdmin(), 403);
        if (trim($note) === '' || mb_strlen($note) > 5000) {
            throw new BusinessRuleException('Isi alasan penyelesaian, maksimal 5000 karakter.');
        }

        return DB::transaction(function () use ($complaint, $admin, $resolution, $note): OrderComplaint {
            $order = Order::query()->lockForUpdate()->findOrFail($complaint->order_id);
            $complaint = OrderComplaint::query()->lockForUpdate()->findOrFail($complaint->id);
            if ($complaint->status !== ComplaintStatus::Terbuka) {
                throw new BusinessRuleException('Komplain ini sudah diselesaikan.');
            }
            if ($resolution !== ComplaintResolution::Tolak
                && $order->complaints()->where('status', ComplaintStatus::Selesai)
                    ->whereIn('resolution', [ComplaintResolution::Refund, ComplaintResolution::CetakUlang])->exists()) {
                throw new BusinessRuleException('Order ini sudah mendapatkan refund atau penggantian.');
            }

            $replacement = null;
            if ($resolution === ComplaintResolution::Refund) {
                $category = TransactionCategory::system('refund');
                if (CashTransaction::where('order_id', $order->id)->where('transaction_category_id', $category->id)->notVoided()->exists()) {
                    throw new BusinessRuleException('Refund order ini sudah tercatat.');
                }
                $payment = $order->payments()->where('status', PaymentStatus::Diterima)->latest('id')->first();
                if ($payment === null || $payment->amount <= 0) {
                    throw new BusinessRuleException('Tidak ada pembayaran diterima untuk direfund.');
                }
                $payment->cashAccount->cashTransactions()->create([
                    'transaction_category_id' => $category->id,
                    'type' => CashFlow::Keluar,
                    'currency' => $payment->currency,
                    'amount' => $payment->amount,
                    'exchange_rate' => $order->exchange_rate,
                    'amount_base' => BaseCurrency::convert($payment->amount, $payment->currency, (float) $order->exchange_rate),
                    'transaction_date' => now('Asia/Tokyo')->toDateString(),
                    'order_id' => $order->id,
                    'description' => "Refund komplain #{$complaint->id} — {$order->code}: {$note}",
                    'recorded_by' => $admin->id,
                ]);
            } elseif ($resolution === ComplaintResolution::CetakUlang) {
                $items = $order->items()->with('design')->get();
                if ($items->isEmpty()) {
                    throw new BusinessRuleException('Order tanpa item tidak dapat dibuatkan penggantian.');
                }
                $batch = $this->ensureOpenBatch->handle();
                $replacement = Order::create([
                    ...$order->only(['user_id', 'currency', 'exchange_rate', 'customer_name', 'customer_phone', 'postal_code', 'prefecture', 'city', 'address_line1', 'address_line2']),
                    'code' => OrderCode::next(),
                    'source' => OrderSource::Penggantian,
                    'parent_order_id' => $order->id,
                    'preorder_batch_id' => $batch->id,
                    'status' => OrderStatus::Dibayar,
                    'subtotal' => 0,
                    'total' => 0,
                    'created_by' => $admin->id,
                ]);
                foreach ($items as $item) {
                    $copy = $replacement->items()->create([
                        ...$item->only(['item_type', 'product_variant_id', 'custom_case_option_id', 'item_name', 'qty', 'unit_cost_idr']),
                        'unit_price' => 0, 'subtotal' => 0, 'is_packed' => false,
                    ]);
                    if ($item->design !== null) {
                        $design = $item->design->replicate(['reviewed_by', 'reviewed_at', 'review_note', 'print_path']);
                        $design->order_item_id = $copy->id;
                        $design->review_status = DesignReviewStatus::Menunggu;
                        $design->save();
                    }
                }
                $replacement->statusHistories()->create([
                    'to_status' => OrderStatus::Dibayar, 'user_id' => $admin->id, 'created_at' => now(),
                    'note' => "Penggantian gratis seluruh order {$order->code}, komplain #{$complaint->id}. {$note}",
                ]);
            }

            $complaint->update([
                'resolution' => $resolution,
                'resolution_note' => $note,
                'replacement_order_id' => $replacement?->id,
                'status' => ComplaintStatus::Selesai,
                'handled_by' => $admin->id,
                'resolved_at' => now(),
            ]);
            $order->statusHistories()->create([
                'from_status' => $order->status, 'to_status' => $order->status,
                'user_id' => $admin->id, 'created_at' => now(),
                'note' => "Komplain #{$complaint->id}: {$resolution->getLabel()}. {$note}",
            ]);

            return $complaint;
        });
    }
}
