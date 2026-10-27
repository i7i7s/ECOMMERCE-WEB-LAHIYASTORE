<?php

namespace App\Policies;

class SupplierPolicy extends AdminOnlyPolicy
{
    protected bool $deletable = true;
}
