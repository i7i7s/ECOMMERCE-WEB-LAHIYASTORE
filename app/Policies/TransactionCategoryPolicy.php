<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TransactionCategoryPolicy extends AdminOnlyPolicy
{
    protected bool $deletable = true;

    /**
     * System categories (penjualan, refund, transfer, ...) are used by the app itself.
     */
    public function delete(User $user, Model $model): bool
    {
        return ! $model->is_system && parent::delete($user, $model);
    }
}
