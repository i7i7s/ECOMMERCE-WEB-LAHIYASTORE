<?php

namespace App\Policies;

class ProductPolicy extends StaffManagedPolicy
{
    protected bool $deletable = true;
}
