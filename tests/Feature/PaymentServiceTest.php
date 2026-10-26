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

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    private User $accountant;

    private Contract $contract;

    private PaymentStage $advanceStage;

    private PaymentStage $deliveryStage;

    private BudgetLine $budgetMarch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PaymentService::class);

        $this->accountant = User::create([
            'name' => 'Buxgalter', 'email' => 'acc@test.com',
            'password' => bcrypt('pw'), 'role' => 'accountant',
        ]);

        $executor = User::create([
            'name' => 'Ijrochi', 'email' => 'ex@test.com',
            'password' => bcrypt('pw'), 'role' => 'executor',
        ]);

        $this->contract = Contract::create([
            'number' => 'T-001', 'executor_id' => $executor->id,
            'supplier' => 'Test', 'start_date' => '2024-03-02',
            'duration_days' => 10, 'status' => 'active',
            'expense_category' => 'Dori vositalari',
        ]);

        $this->advanceStage = PaymentStage::create([
            'contract_id' => $this->contract->id, 'name' => 'Avans',
            'percentage' => '30', 'amount' => '750.00',
            'sort_order' => 1, 'condition' => 'after_activation',
        ]);

        $this->deliveryStage = PaymentStage::create([
            'contract_id' => $this->contract->id, 'name' => 'Yetkazib berish',
            'percentage' => '70', 'amount' => '1750.00',
            'sort_order' => 2, 'condition' => 'after_full_delivery',
        ]);

        $this->budgetMarch = BudgetLine::create([
            'fiscal_year' => 2024, 'expense_category' => 'Dori vositalari',
            'month' => 3, 'allocated_amount' => '3000.00',
        ]);
    }

    #[Test]
    public function example_a_draft_contract_rejects_advance(): void
    {
        $this->contract->update(['status' => 'draft']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Faol yoki Yakunlangan/');

        $this->service->record([
            'payment_stage_id' => $this->advanceStage->id,
            'budget_line_id' => $this->budgetMarch->id,
            'amount' => '750.00',
            'payment_date' => '2024-03-05',
        ], $this->accountant);
    }

    #[Test]
    public function example_a_active_contract_advance_accepted(): void
    {
        $payment = $this->service->record([
            'payment_stage_id' => $this->advanceStage->id,
            'budget_line_id' => $this->budgetMarch->id,
            'amount' => '750.00',
            'payment_date' => '2024-03-05',
        ], $this->accountant);

        $this->assertFalse($payment->is_voided);
        $this->assertSame('750.00', $payment->amount);
        $this->assertSame('2250.00', $this->budgetMarch->fresh()->remainingAmount());
        $this->assertSame('paid', $this->advanceStage->fresh()->computedStatus());
    }

    #[Test]
    public function example_b_delivery_stage_rejected_before_full_delivery(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/to\'lov sharti/i');

        $this->service->record([
            'payment_stage_id' => $this->deliveryStage->id,
            'budget_line_id' => $this->budgetMarch->id,
            'amount' => '1750.00',
            'payment_date' => '2024-03-20',
        ], $this->accountant);
    }

    #[Test]
    public function previous_stage_must_be_paid_first(): void
    {
        $this->contract->update(['status' => 'completed']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Avans.*to\'liq to\'lanmagan/i');

        $this->service->record([
            'payment_stage_id' => $this->deliveryStage->id,
            'budget_line_id' => $this->budgetMarch->id,
            'amount' => '500.00',
            'payment_date' => '2024-03-20',
        ], $this->accountant);
    }

    #[Test]
    public function amount_exceeding_stage_remaining_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/bosqich qoldig/i');

        $this->service->record([
            'payment_stage_id' => $this->advanceStage->id,
            'budget_line_id' => $this->budgetMarch->id,
            'amount' => '800.00',
            'payment_date' => '2024-03-05',
        ], $this->accountant);
    }

    #[Test]
    public function payment_date_year_mismatch_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/moliyaviy yil/i');

        $this->service->record([
            'payment_stage_id' => $this->advanceStage->id,
            'budget_line_id' => $this->budgetMarch->id,
            'amount' => '750.00',
            'payment_date' => '2025-03-05',
        ], $this->accountant);
    }

    #[Test]
    public function budget_insufficient_rejected(): void
    {
        BudgetLine::where('id', $this->budgetMarch->id)->update(['allocated_amount' => '100.00']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/smeta qoldig/i');

        $this->service->record([
            'payment_stage_id' => $this->advanceStage->id,
            'budget_line_id' => $this->budgetMarch->id,
            'amount' => '750.00',
            'payment_date' => '2024-03-05',
        ], $this->accountant);
    }

    #[Test]
    public function example_b_partial_payment_updates_status(): void
    {
        $this->service->record([
            'payment_stage_id' => $this->advanceStage->id,
            'budget_line_id' => $this->budgetMarch->id,
            'amount' => '300.00',
            'payment_date' => '2024-03-05',
        ], $this->accountant);

        $this->assertSame('partial', $this->advanceStage->fresh()->computedStatus());
        $this->assertSame('450.00', $this->advanceStage->fresh()->remainingAmount());
    }

    #[Test]
    public function example_c_void_and_re_record(): void
    {
        $this->service->record([
            'payment_stage_id' => $this->advanceStage->id,
            'budget_line_id' => $this->budgetMarch->id,
            'amount' => '750.00',
            'payment_date' => '2024-03-05',
        ], $this->accountant);

        $this->contract->update(['status' => 'completed']);

        $wrongPayment = $this->service->record([
            'payment_stage_id' => $this->deliveryStage->id,
            'budget_line_id' => $this->budgetMarch->id,
            'amount' => '1000.00',
            'payment_date' => '2024-03-20',
        ], $this->accountant);

        $voided = $this->service->void($wrongPayment, 'Adashib kiritildi', $this->accountant);
        $this->assertTrue($voided->is_voided);
        $this->assertDatabaseHas('payments', ['id' => $wrongPayment->id, 'is_voided' => true]);

        $this->assertSame('1750.00', $this->deliveryStage->fresh()->remainingAmount());
        $this->assertSame('2250.00', $this->budgetMarch->fresh()->remainingAmount());

        $correct = $this->service->record([
            'payment_stage_id' => $this->deliveryStage->id,
            'budget_line_id' => $this->budgetMarch->id,
            'amount' => '750.00',
            'payment_date' => '2024-03-20',
        ], $this->accountant);

        $this->assertSame('750.00', $correct->amount);
        $this->assertSame('1000.00', $this->deliveryStage->fresh()->remainingAmount());
    }

    #[Test]
    public function cannot_void_already_voided_payment(): void
    {
        $payment = $this->service->record([
            'payment_stage_id' => $this->advanceStage->id,
            'budget_line_id' => $this->budgetMarch->id,
            'amount' => '750.00',
            'payment_date' => '2024-03-05',
        ], $this->accountant);

        $this->service->void($payment, 'Sabab', $this->accountant);

        $this->expectException(\RuntimeException::class);
        $this->service->void($payment->fresh(), 'Sabab 2', $this->accountant);
    }
}
