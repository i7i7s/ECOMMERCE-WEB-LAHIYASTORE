<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin and staff see and create orders (manual WA/TikTok). Orders are never edited
 * freely: every change goes through an action that follows the status rules.
 */
class OrderPolicy extends StaffManagedPolicy
{
    public function update(User $user, Model $model): bool
    {
        return false;
    }
}
