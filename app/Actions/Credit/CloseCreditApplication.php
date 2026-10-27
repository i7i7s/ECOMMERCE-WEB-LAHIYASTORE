<?php

namespace App\Actions\Credit;

use App\Enums\CreditApplicationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CreditApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Ends a credit application before money changed hands: the admin rejects a new one,
 * or it is cancelled (by the customer while it is still being reviewed, by the admin
 * until the down payment is paid). The unpaid schedule is removed.
 */
class CloseCreditApplication
{
    public function reject(CreditApplication $application, User $admin, string $reason): CreditApplication
    {
        if (! $admin->isAdmin() || ! $admin->is_active) {
            throw new BusinessRuleException('Hanya admin yang bisa menolak kredit.');
        }

        if ($application->status !== CreditApplicationStatus::Diajukan) {
            throw new BusinessRuleException('Hanya pengajuan baru yang bisa ditolak.');
        }

        return $this->close($application, CreditApplicationStatus::Ditolak, $admin, $reason);
    }

    public function cancel(CreditApplication $application, User $by, string $reason): CreditApplication
    {
        $isOwner = $application->user_id === $by->id;
        $isAdmin = $by->isAdmin() && $by->is_active;

        $allowed = match ($application->status) {
            CreditApplicationStatus::Diajukan => $isOwner || $isAdmin,
            CreditApplicationStatus::Disetujui => $isAdmin && ! $application->installments()->whereNotNull('paid_at')->exists(),
            default => false,
        };

        if (! $allowed) {
            throw new BusinessRuleException($isOwner && ! $isAdmin
                ? 'Pengajuan yang sudah disetujui hanya bisa dibatalkan lewat admin.'
                : 'Kredit yang sudah ada pembayarannya tidak bisa dibatalkan.');
        }

        return $this->close($application, CreditApplicationStatus::Dibatalkan, $by, $reason);
    }

    private function close(CreditApplication $application, CreditApplicationStatus $status, User $by, string $reason): CreditApplication
    {
        if (blank($reason)) {
            throw new BusinessRuleException('Tulis alasannya.');
        }

        return DB::transaction(function () use ($application, $status, $by, $reason): CreditApplication {
            $application->installments()->whereNull('paid_at')->delete();
            $application->update([
                'status' => $status,
                'decided_by' => $by->id,
                'decided_at' => now(),
                'admin_note' => mb_substr(trim($reason), 0, 1000),
            ]);

            return $application;
        });
    }
}
