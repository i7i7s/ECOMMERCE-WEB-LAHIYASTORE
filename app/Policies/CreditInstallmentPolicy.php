<?php

namespace App\Policies;

use App\Models\User;

/**
 * Payment schedules are generated when a credit is approved, never typed in by hand.
 */
class CreditInstallmentPolicy extends AdminOnlyPolicy
{
    public function create(User $user): bool
    {
        return false;
    }
}
