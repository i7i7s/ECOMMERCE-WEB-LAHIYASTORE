<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderComplaintPolicy extends StaffManagedPolicy
{
    public function update(User $user, Model $model): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
