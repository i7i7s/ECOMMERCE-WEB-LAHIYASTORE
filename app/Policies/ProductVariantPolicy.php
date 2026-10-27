<?php

namespace App\Policies;

class ProductVariantPolicy extends StaffManagedPolicy
{
    protected bool $deletable = true;
}
