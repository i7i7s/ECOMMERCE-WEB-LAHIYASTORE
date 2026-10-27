<?php

namespace App\Actions\Credit;

use App\Enums\CreditVerification;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\User;

/**
 * Admin verifies a customer for credit (optionally with a limit in yen), or rejects / revokes it.
 */
class DecideCreditVerification
{
    public function verify(User $customer, User $admin, ?int $limit = null, ?string $note = null): User
    {
        $this->guard($customer, $admin);

        if ($limit !== null && $limit < 1) {
            throw new BusinessRuleException('Batas kredit harus lebih dari 0, atau kosongkan untuk tanpa batas.');
        }

        $customer->update([
            'credit_status' => CreditVerification::Terverifikasi,
            'credit_verified_at' => now(),
            'credit_verified_by' => $admin->id,
            'credit_limit' => $limit,
            'credit_note' => $note,
        ]);

        return $customer;
    }

    public function reject(User $customer, User $admin, string $note): User
    {
        $this->guard($customer, $admin);

        if (blank($note)) {
            throw new BusinessRuleException('Tulis alasannya.');
        }

        $customer->update([
            'credit_status' => CreditVerification::Ditolak,
            'credit_verified_at' => null,
            'credit_verified_by' => $admin->id,
            'credit_note' => $note,
        ]);

        return $customer;
    }

    private function guard(User $customer, User $admin): void
    {
        if (! $admin->isAdmin() || ! $admin->is_active) {
            throw new BusinessRuleException('Hanya admin yang bisa memverifikasi kredit.');
        }

        if ($customer->role !== UserRole::Customer) {
            throw new BusinessRuleException('Verifikasi kredit hanya untuk akun pelanggan.');
        }
    }
}
