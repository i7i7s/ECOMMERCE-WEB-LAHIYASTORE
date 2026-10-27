<?php

namespace App\Policies;

class CaseTypePolicy extends AdminOnlyPolicy
{
    protected bool $deletable = true;
}
