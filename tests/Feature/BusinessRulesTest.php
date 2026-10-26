<?php

namespace Tests\Feature;

use App\Models\BudgetLine;
use App\Models\Contract;
use App\Models\PaymentStage;
use App\Models\Penalty;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $email = ''): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $email ?: "{$role}@test.com",
            'password' => bcrypt('pw'),
            'role' => $role,
        ]);
    }

    private function makeContract(User $executor, string $status = 'draft'): Contract
    {
        return Contract::create([
            'number' => uniqid('C-'),
            'executor_id' => $executor->id,
            'supplier' => 'Test',
            'start_date' => '2024-03-02',
            'duration_days' => 10,
            'status' => $status,
            'expense_category' => 'Dori vositalari',
        ]);
    }

    // ── Misol D: Jarima tasdiqlanmasa buxgalteriyada 0.00 ─────────────────

    #[Test]
    public function example_d_pending_penalty_shows_zero_in_accounting(): void
    {
        $contract = $this->makeContract($this->makeUser('executor'), 'active');

        $penalty = Penalty::create([
            'contract_id' => $contract->id,
            'calculated_at' => '2024-03-15',
            'calculated_amount' => '37.50',
            'overdue_days' => 3,
            'limit_amount' => '1250.00',
            'status' => 'pending',
        ]);

        $this->assertSame('0.00', $penalty->effectiveAmount());
    }

    #[Test]
    public function example_d_approved_penalty_shows_approved_amount(): void
    {
        $contract = $this->makeContract($this->makeUser('executor'), 'active');

        $penalty = Penalty::create([
            'contract_id' => $contract->id,
            'calculated_at' => '2024-03-15',
            'calculated_amount' => '37.50',
            'approved_amount' => '30.00',
            'overdue_days' => 3,
            'limit_amount' => '1250.00',
            'status' => 'approved',
        ]);

        // Boshliq 30.00 ga o'zgartirdi — buxgalteriyada 30.00, hisoblangan 37.50 emas
        $this->assertSame('30.00', $penalty->effectiveAmount());
    }

    #[Test]
    public function example_d_rejected_penalty_shows_zero(): void
    {
        $contract = $this->makeContract($this->makeUser('executor'), 'active');

        $penalty = Penalty::create([
            'contract_id' => $contract->id,
            'calculated_at' => '2024-03-15',
            'calculated_amount' => '37.50',
            'overdue_days' => 3,
            'limit_amount' => '1250.00',
            'status' => 'rejected',
        ]);

        $this->assertSame('0.00', $penalty->effectiveAmount());
    }

    // ── PenaltyPolicy: faqat boshliq tasdiqlaydi ──────────────────────────

    #[Test]
    public function chief_can_review_pending_penalty(): void
    {
        $contract = $this->makeContract($this->makeUser('executor'), 'active');
        $chief = $this->makeUser('chief', 'chief@test.com');

        $penalty = Penalty::create([
            'contract_id' => $contract->id,
            'calculated_at' => '2024-03-15',
            'calculated_amount' => '37.50',
            'overdue_days' => 3,
            'limit_amount' => '1250.00',
            'status' => 'pending',
        ]);

        $this->actingAs($chief)
            ->post(route('penalties.review', $penalty), [
                'action' => 'approve',
                'approved_amount' => '37.50',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $penalty->fresh()->status);
    }

    #[Test]
    public function accountant_cannot_review_penalty(): void
    {
        $contract = $this->makeContract($this->makeUser('executor'), 'active');
        $accountant = $this->makeUser('accountant', 'acc@test.com');

        $penalty = Penalty::create([
            'contract_id' => $contract->id,
            'calculated_at' => '2024-03-15',
            'calculated_amount' => '37.50',
            'overdue_days' => 3,
            'limit_amount' => '1250.00',
            'status' => 'pending',
        ]);

        $this->actingAs($accountant)
            ->post(route('penalties.review', $penalty), ['action' => 'approve'])
            ->assertForbidden();

        $this->assertSame('pending', $penalty->fresh()->status);
    }

    // ── Yetkazish: ortiqcha kiritilsa aniq xato ───────────────────────────

    #[Test]
    public function delivery_paid_quantity_overflow_is_rejected(): void
    {
        $executor = $this->makeUser('executor');
        $contract = $this->makeContract($executor, 'active');

        $item = $contract->items()->create([
            'name' => 'Paracetamol 500mg', 'paid_quantity' => 100,
            'bonus_quantity' => 10, 'unit_price' => '2.50',
        ]);

        $this->actingAs($executor)
            ->post(route('deliveries.store', $contract), [
                'delivery_date' => '2024-03-10',
                'items' => [[
                    'contract_item_id' => $item->id,
                    'paid_quantity' => 101,  // qoldiq 100
                    'bonus_quantity' => 0,
                ]],
            ])
            ->assertSessionHasErrors();

        $this->assertSame(0, $contract->deliveries()->count());
    }

    #[Test]
    public function delivery_bonus_quantity_overflow_is_rejected(): void
    {
        $executor = $this->makeUser('executor');
        $contract = $this->makeContract($executor, 'active');

        $item = $contract->items()->create([
            'name' => 'Vitamin C', 'paid_quantity' => 100,
            'bonus_quantity' => 5, 'unit_price' => '3.00',
        ]);

        $this->actingAs($executor)
            ->post(route('deliveries.store', $contract), [
                'delivery_date' => '2024-03-10',
                'items' => [[
                    'contract_item_id' => $item->id,
                    'paid_quantity' => 0,
                    'bonus_quantity' => 6,  // qoldiq 5
                ]],
            ])
            ->assertSessionHasErrors();
    }

    // ── Shartnoma: itemsiz faollashtirish rad etiladi ─────────────────────

    #[Test]
    public function contract_cannot_be_activated_without_items(): void
    {
        $executor = $this->makeUser('executor');
        $contract = $this->makeContract($executor, 'draft');

        $this->actingAs($executor)
            ->post(route('contracts.activate', $contract))
            ->assertSessionHasErrors();

        $this->assertSame('draft', $contract->fresh()->status);
    }

    #[Test]
    public function contract_activates_with_items_and_creates_payment_stages(): void
    {
        $executor = $this->makeUser('executor');
        $contract = $this->makeContract($executor, 'draft');

        $contract->items()->create([
            'name' => 'Paracetamol', 'paid_quantity' => 1000,
            'bonus_quantity' => 50, 'unit_price' => '2.50',
        ]);

        $this->actingAs($executor)
            ->post(route('contracts.activate', $contract))
            ->assertRedirect();

        $this->assertSame('active', $contract->fresh()->status);
        $this->assertSame(2, $contract->paymentStages()->count());
        $this->assertSame(0, bccomp('750.00', $contract->paymentStages()->orderBy('sort_order')->first()->amount, 2));
    }

    // ── Void: smeta qoldig'i va bosqich qoldig'i tiklanadi ────────────────

    #[Test]
    public function voided_payment_restores_budget_and_stage_remaining(): void
    {
        $accountant = $this->makeUser('accountant');
        $executor = $this->makeUser('executor', 'ex@test.com');
        $contract = $this->makeContract($executor, 'active');

        $stage = PaymentStage::create([
            'contract_id' => $contract->id, 'name' => 'Avans',
            'percentage' => '30', 'amount' => '750.00',
            'sort_order' => 1, 'condition' => 'after_activation',
        ]);

        $budget = BudgetLine::create([
            'fiscal_year' => 2024, 'expense_category' => 'Dori vositalari',
            'month' => 3, 'allocated_amount' => '3000.00',
        ]);

        $service = app(PaymentService::class);
        $payment = $service->record([
            'payment_stage_id' => $stage->id,
            'budget_line_id' => $budget->id,
            'amount' => '750.00',
            'payment_date' => '2024-03-05',
        ], $accountant);

        $this->assertSame('2250.00', $budget->fresh()->remainingAmount());

        $service->void($payment, 'Adashib kiritildi', $accountant);

        // Smeta va bosqich tiklanadi
        $this->assertSame('3000.00', $budget->fresh()->remainingAmount());
        $this->assertSame('750.00', $stage->fresh()->remainingAmount());
        // Yozuv o'chirilmaydi — DB da saqlanadi
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'is_voided' => true]);
    }
}
