<?php

namespace App\Policies;

use App\Models\User;

/**
 * Admin and staff review designs (PRD §4). Designs are created only through orders.
 */
class OrderItemDesignPolicy extends StaffManagedPolicy
{
    public function create(User $user): bool
    {
        return false;
    }
}
