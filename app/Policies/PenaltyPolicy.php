<?php

namespace App\Policies;

use App\Models\Penalty;
use App\Models\User;

class PenaltyPolicy
{
    public function review(User $user, Penalty $penalty): bool
    {
        return ($user->isChief() || $user->isSuperAdmin()) && $penalty->isPending();
    }
}
