<?php

namespace App\Policies;

class PhoneModelPolicy extends AdminOnlyPolicy
{
    protected bool $deletable = true;
}
