<?php

namespace App\Policies;

class CategoryPolicy extends StaffManagedPolicy
{
    protected bool $deletable = true;
}
