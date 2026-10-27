<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Records only the owner (admin) manages: phone models, custom prices & costs, staff accounts (PRD §4).
 * Only master-data policies opt in to deletion; the database still refuses rows that orders
 * or the cash book refer to, so those are deactivated instead.
 */
abstract class AdminOnlyPolicy
{
    /** Whether unused records of this model may be deleted from the admin panel. */
    protected bool $deletable = false;

    public function viewAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->deletable && $this->isActiveAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    protected function isActiveAdmin(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
