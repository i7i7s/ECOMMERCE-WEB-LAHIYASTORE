<?php

namespace App\Policies;

class CaseColorPolicy extends AdminOnlyPolicy
{
    protected bool $deletable = true;
}
