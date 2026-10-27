<?php

namespace App\Policies;

class CustomCaseOptionPolicy extends AdminOnlyPolicy
{
    protected bool $deletable = true;
}
