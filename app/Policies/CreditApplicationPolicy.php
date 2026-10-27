<?php

namespace App\Policies;

use App\Models\User;

/**
 * Credit purchases are handled by the owner only. They are created by customers on the store,
 * never from the admin panel.
 */
class CreditApplicationPolicy extends AdminOnlyPolicy
{
    public function create(User $user): bool
    {
        return false;
    }
}
