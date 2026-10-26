<?php

namespace Tests\Feature;

use App\Models\BudgetLine;
use App\Models\Contract;
use App\Models\PaymentStage;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConcurrentPaymentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function only_one_of_two_concurrent_payments_succeeds_when_budget_is_tight(): void
    {
        $accountant1 = User::create([
            'name' => 'Acc1', 'email' => 'acc1@test.com',
            'password' => bcrypt('pw'), 'role' => 'accountant',
        ]);
        $accountant2 = User::create([
            'name' => 'Acc2', 'email' => 'acc2@test.com',
            'password' => bcrypt('pw'), 'role' => 'accountant',
        ]);
        $executor = User::create([
            'name' => 'Ex', 'email' => 'ex@test.com',
            'password' => bcrypt('pw'), 'role' => 'executor',
        ]);

        // Budget with only 750
        $budget = BudgetLine::create([
            'fiscal_year' => 2024, 'expense_category' => 'Test',
            'month' => 3, 'allocated_amount' => '750.00',
        ]);

        $contract1 = $this->makeActiveContract($executor, 'C1', '750.00');
        $contract2 = $this->makeActiveContract($executor, 'C2', '750.00');

        $stage1 = $contract1->paymentStages()->first();
        $stage2 = $contract2->paymentStages()->first();

        $service = app(PaymentService::class);
        $success = 0;
        $failures = 0;

        foreach ([$accountant1, $accountant2] as $idx => $acc) {
            $stage = $idx === 0 ? $stage1 : $stage2;
            try {
                $service->record([
                    'payment_stage_id' => $stage->id,
                    'budget_line_id' => $budget->id,
                    'amount' => '750.00',
                    'payment_date' => '2024-03-05',
                ], $acc);
                $success++;
            } catch (\RuntimeException $e) {
                $failures++;
            }
        }

        $this->assertSame(1, $success, 'Exactly one payment should succeed');
        $this->assertSame(1, $failures, 'Exactly one payment should fail');
        $this->assertSame('0.00', $budget->fresh()->remainingAmount());
    }

    private function makeActiveContract(User $executor, string $number, string $totalAmount): Contract
    {
        $contract = Contract::create([
            'number' => $number, 'executor_id' => $executor->id,
            'supplier' => 'Test', 'start_date' => '2024-03-02',
            'duration_days' => 10, 'status' => 'active',
            'expense_category' => 'Test',
        ]);

        $contract->items()->create([
            'name' => 'Item', 'paid_quantity' => 1,
            'bonus_quantity' => 0, 'unit_price' => $totalAmount,
        ]);

        PaymentStage::create([
            'contract_id' => $contract->id, 'name' => 'Avans',
            'percentage' => '100', 'amount' => $totalAmount,
            'sort_order' => 1, 'condition' => 'after_activation',
        ]);

        return $contract;
    }
}
