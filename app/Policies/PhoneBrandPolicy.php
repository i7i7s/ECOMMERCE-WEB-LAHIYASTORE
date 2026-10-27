<?php

namespace App\Policies;

class PhoneBrandPolicy extends AdminOnlyPolicy
{
    protected bool $deletable = true;
}
