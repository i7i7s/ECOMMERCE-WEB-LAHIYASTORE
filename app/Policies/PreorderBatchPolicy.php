<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Staff may view batches and download the recap; only the admin changes them (PRD §4).
 * Batches are opened by the system, never created by hand.
 */
class PreorderBatchPolicy extends StaffManagedPolicy
{
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $model): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
