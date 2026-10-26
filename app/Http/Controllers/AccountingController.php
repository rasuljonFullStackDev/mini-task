<?php

namespace App\Http\Controllers;

use App\Models\BudgetLine;
use App\Models\Contract;
use Illuminate\View\View;

class AccountingController extends Controller
{
    public function index(): View
    {
        $this->authorizeAccountant();
        $contracts = Contract::with('paymentStages.payments', 'penalties')
            ->where('status', '!=', 'draft')
            ->paginate(20);
        $budgetLines = BudgetLine::all();

        return view('accounting.index', compact('contracts', 'budgetLines'));
    }

    public function show(Contract $contract): View
    {
        $this->authorizeAccountant();
        $contract->load(
            'items',
            'paymentStages.payments.createdByUser',
            'paymentStages.payments.voidedByUser',
            'paymentStages.payments.budgetLine',
            'penalties'
        );
        $budgetLines = BudgetLine::all();

        return view('accounting.show', compact('contract', 'budgetLines'));
    }

    private function authorizeAccountant(): void
    {
        if (! auth()->user()->isAccountant() && ! auth()->user()->isSuperAdmin()) {
            abort(403);
        }
    }
}
