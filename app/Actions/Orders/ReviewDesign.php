<?php

namespace App\Actions\Orders;

use App\Enums\DesignReviewStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\OrderItemDesign;
use App\Models\User;

/**
 * Staff or admin approves a custom design, or asks for a revision with a note (CUST-11).
 */
class ReviewDesign
{
    public function handle(OrderItemDesign $design, User $reviewer, DesignReviewStatus $decision, ?string $note = null): OrderItemDesign
    {
        if ($decision === DesignReviewStatus::Menunggu) {
            throw new BusinessRuleException('Pilih setujui atau minta revisi.');
        }

        if ($decision === DesignReviewStatus::Revisi && blank($note)) {
            throw new BusinessRuleException('Tulis catatan revisi untuk pelanggan.');
        }

        $design->update([
            'review_status' => $decision,
            'review_note' => $note,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $design;
    }
}
