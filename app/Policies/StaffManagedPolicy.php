<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Records that both admin and staff manage (PRD §4). Only catalog policies opt in to
 * deletion; the database still refuses rows that orders refer to, so those are deactivated instead.
 */
abstract class StaffManagedPolicy
{
    /** Whether unused records of this model may be deleted from the admin panel. */
    protected bool $deletable = false;

    public function viewAny(User $user): bool
    {
        return $this->isTeamMember($user);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->isTeamMember($user);
    }

    public function create(User $user): bool
    {
        return $this->isTeamMember($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->isTeamMember($user);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->deletable && $this->isTeamMember($user);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    protected function isTeamMember(User $user): bool
    {
        return $user->is_active && in_array($user->role, [UserRole::Admin, UserRole::Staff], true);
    }
}
