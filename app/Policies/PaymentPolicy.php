<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function create(User $user): bool
    {
        return $user->isAccountant() || $user->isSuperAdmin();
    }

    public function void(User $user, Payment $payment): bool
    {
        return ($user->isAccountant() || $user->isSuperAdmin()) && ! $payment->is_voided;
    }
}
