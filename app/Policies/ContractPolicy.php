<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    /**
     * Chief sees all contracts; executor sees only their own.
     */
    public function view(User $user, Contract $contract): bool
    {
        if ($user->isSuperAdmin() || $user->isChief() || $user->isAccountant()) {
            return true;
        }

        return $user->id === $contract->executor_id;
    }

    public function create(User $user): bool
    {
        return $user->isExecutor();
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->isExecutor() && $user->id === $contract->executor_id && $contract->isDraft();
    }

    public function activate(User $user, Contract $contract): bool
    {
        return $user->isExecutor() && $user->id === $contract->executor_id && $contract->isDraft();
    }

    public function addDelivery(User $user, Contract $contract): bool
    {
        return $user->isExecutor() && $user->id === $contract->executor_id && ($contract->isActive() || $contract->isCompleted());
    }
}
