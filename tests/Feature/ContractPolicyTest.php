<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContractPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $executor1;

    private User $executor2;

    private User $chief;

    private User $accountant;

    private Contract $contract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->executor1 = User::create([
            'name' => 'Ex1', 'email' => 'ex1@test.com',
            'password' => bcrypt('pw'), 'role' => 'executor',
        ]);
        $this->executor2 = User::create([
            'name' => 'Ex2', 'email' => 'ex2@test.com',
            'password' => bcrypt('pw'), 'role' => 'executor',
        ]);
        $this->chief = User::create([
            'name' => 'Chief', 'email' => 'chief@test.com',
            'password' => bcrypt('pw'), 'role' => 'chief',
        ]);
        $this->accountant = User::create([
            'name' => 'Acc', 'email' => 'acc@test.com',
            'password' => bcrypt('pw'), 'role' => 'accountant',
        ]);

        $this->contract = Contract::create([
            'number' => 'P-001', 'executor_id' => $this->executor1->id,
            'supplier' => 'Test', 'start_date' => '2024-03-02',
            'duration_days' => 10, 'status' => 'draft',
            'expense_category' => 'Dori',
        ]);
    }

    #[Test]
    public function executor_can_only_view_own_contract(): void
    {
        $this->actingAs($this->executor1)
            ->get(route('contracts.show', $this->contract))
            ->assertOk();

        $this->actingAs($this->executor2)
            ->get(route('contracts.show', $this->contract))
            ->assertForbidden();
    }

    #[Test]
    public function chief_can_view_all_contracts(): void
    {
        $this->actingAs($this->chief)
            ->get(route('contracts.show', $this->contract))
            ->assertOk();
    }

    #[Test]
    public function accountant_can_view_all_contracts(): void
    {
        $this->actingAs($this->accountant)
            ->get(route('contracts.show', $this->contract))
            ->assertOk();
    }

    #[Test]
    public function only_executor_can_create_contract(): void
    {
        $this->actingAs($this->chief)
            ->get(route('contracts.create'))
            ->assertForbidden();

        $this->actingAs($this->accountant)
            ->get(route('contracts.create'))
            ->assertForbidden();

        $this->actingAs($this->executor1)
            ->get(route('contracts.create'))
            ->assertOk();
    }

    #[Test]
    public function executor_cannot_access_accounting(): void
    {
        $this->actingAs($this->executor1)
            ->get(route('accounting.index'))
            ->assertForbidden();
    }
}
