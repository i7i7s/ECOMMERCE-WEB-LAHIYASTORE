<?php

namespace App\Actions\Batches;

use App\Actions\Orders\TransitionOrderStatus;
use App\Enums\BatchStatus;
use App\Enums\DesignReviewStatus;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\OrderItemDesign;
use App\Models\PreorderBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Moves a closed batch to its next status and every order inside it along with it,
 * recording the change on each order (PRD §6.1, BATCH-03).
 */
class AdvanceBatch
{
    public function __construct(private TransitionOrderStatus $transition) {}

    /**
     * @param  array{intl_courier?: string, intl_tracking_number?: string, eta_date?: string}  $shipping  Required when shipping to Japan.
     */
    public function handle(PreorderBatch $batch, User $admin, array $shipping = []): PreorderBatch
    {
        if (! $admin->isAdmin()) {
            throw new BusinessRuleException('Hanya admin yang bisa mengubah status batch.');
        }

        $target = $batch->status->next();

        if ($batch->status === BatchStatus::Dibuka || $target === null) {
            throw new BusinessRuleException($target === null ? "Batch {$batch->code} sudah selesai." : 'Tutup batch dulu sebelum diproduksi.');
        }

        $this->guard($batch, $target, $shipping);

        return DB::transaction(function () use ($batch, $admin, $target, $shipping): PreorderBatch {
            $batch->update([
                'status' => $target,
                ...match ($target) {
                    BatchStatus::Diproduksi => ['produced_at' => now()],
                    BatchStatus::DikirimKeJepang => [
                        'intl_courier' => $shipping['intl_courier'],
                        'intl_tracking_number' => $shipping['intl_tracking_number'],
                        'eta_date' => $shipping['eta_date'],
                        'shipped_at' => now(),
                    ],
                    BatchStatus::TibaDiJepang => ['arrived_at' => now()],
                    BatchStatus::Selesai => ['completed_at' => now()],
                    default => [],
                },
            ]);

            $orderStatus = $target->orderStatus();

            if ($orderStatus !== null) {
                $previous = match ($orderStatus) {
                    OrderStatus::Diproduksi => OrderStatus::Dibayar,
                    OrderStatus::DikirimKeJepang => OrderStatus::Diproduksi,
                    default => OrderStatus::DikirimKeJepang,
                };

                $batch->orders()->where('status', $previous)->get()->each(
                    fn ($order) => $this->transition->handle($order, $orderStatus, $admin, "Batch {$batch->code}: {$target->getLabel()}"),
                );
            }

            return $batch;
        });
    }

    /**
     * @param  array<string, mixed>  $shipping
     */
    private function guard(PreorderBatch $batch, BatchStatus $target, array $shipping): void
    {
        if ($target === BatchStatus::Diproduksi) {
            $unreviewed = OrderItemDesign::query()
                ->whereIn('review_status', [DesignReviewStatus::Menunggu, DesignReviewStatus::Revisi])
                ->whereHas('orderItem.order', fn ($query) => $query->whereBelongsTo($batch, 'batch')->where('status', OrderStatus::Dibayar))
                ->count();

            if ($unreviewed > 0) {
                throw new BusinessRuleException("{$unreviewed} desain belum disetujui. Selesaikan review atau pindahkan order ke batch berikutnya.");
            }
        }

        if ($target === BatchStatus::DikirimKeJepang && (blank($shipping['intl_courier'] ?? null) || blank($shipping['intl_tracking_number'] ?? null) || blank($shipping['eta_date'] ?? null))) {
            throw new BusinessRuleException('Isi kurir, nomor resi internasional, dan ETA.');
        }

        if ($target === BatchStatus::Selesai) {
            $pending = $batch->orders()->whereNotIn('status', [OrderStatus::Dikirim, OrderStatus::Selesai, OrderStatus::Dibatalkan])->count();

            if ($pending > 0) {
                throw new BusinessRuleException("{$pending} order di batch ini belum dikirim.");
            }
        }
    }
}
