<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin and staff can see payments and proofs; only the admin verifies them (see AcceptPayment).
 */
class PaymentPolicy extends StaffManagedPolicy
{
    public function update(User $user, Model $model): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
