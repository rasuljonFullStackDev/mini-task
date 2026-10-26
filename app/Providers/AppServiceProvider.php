<?php

namespace App\Providers;

use App\Models\Contract;
use App\Models\Payment;
use App\Models\Penalty;
use App\Policies\ContractPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PenaltyPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Contract::class, ContractPolicy::class);
        Gate::policy(Penalty::class, PenaltyPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
    }
}
