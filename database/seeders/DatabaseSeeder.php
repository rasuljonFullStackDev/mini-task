<?php

namespace Database\Seeders;

use App\Models\BudgetLine;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\PaymentStage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@admin.com',
            'password' => Hash::make('admin123'),
            'role'     => 'superadmin',
        ]);

        // 3 demo users
        $executor = User::create([
            'name'     => 'Ijrochi Alisher',
            'email'    => 'ijrochi@demo.test',
            'password' => Hash::make('password'),
            'role'     => 'executor',
        ]);

        $chief = User::create([
            'name'     => 'Boshliq Bahodir',
            'email'    => 'boshliq@demo.test',
            'password' => Hash::make('password'),
            'role'     => 'chief',
        ]);

        User::create([
            'name'     => 'Buxgalter Dilnoza',
            'email'    => 'buxgalter@demo.test',
            'password' => Hash::make('password'),
            'role'     => 'accountant',
        ]);

        // Budget lines (smeta)
        $budgetMarch = BudgetLine::create([
            'fiscal_year'      => 2024,
            'expense_category' => 'Dori vositalari',
            'month'            => 3,
            'allocated_amount' => '3000.00',
        ]);

        BudgetLine::create([
            'fiscal_year'      => 2024,
            'expense_category' => 'Dori vositalari',
            'month'            => 4,
            'allocated_amount' => '2000.00',
        ]);

        // Contract 1: demo contract from the task (active, has deliveries)
        $contract1 = Contract::create([
            'number'           => 'SHN-2024-001',
            'executor_id'      => $executor->id,
            'supplier'         => 'MediSupply MChJ',
            'start_date'       => '2024-03-02',
            'duration_days'    => 10,
            'expense_category' => 'Dori vositalari',
            'status'           => 'active',
        ]);

        $item1 = $contract1->items()->create([
            'name'           => 'Paracetamol 500mg',
            'paid_quantity'  => 1000,
            'bonus_quantity' => 50,
            'unit_price'     => '2.50',
        ]);

        // Payment stages for contract1
        PaymentStage::create([
            'contract_id' => $contract1->id,
            'name'        => 'Avans',
            'percentage'  => '30',
            'amount'      => '750.00',
            'sort_order'  => 1,
            'condition'   => 'after_activation',
        ]);
        PaymentStage::create([
            'contract_id' => $contract1->id,
            'name'        => 'Yetkazib berish',
            'percentage'  => '70',
            'amount'      => '1750.00',
            'sort_order'  => 2,
            'condition'   => 'after_full_delivery',
        ]);

        // Contract 2: draft
        $contract2 = Contract::create([
            'number'           => 'SHN-2024-002',
            'executor_id'      => $executor->id,
            'supplier'         => 'PharmaTech LLC',
            'start_date'       => '2024-04-01',
            'duration_days'    => 14,
            'expense_category' => 'Dori vositalari',
            'status'           => 'draft',
        ]);
        $contract2->items()->create([
            'name'           => 'Amoxicillin 250mg',
            'paid_quantity'  => 500,
            'bonus_quantity' => 0,
            'unit_price'     => '5.00',
        ]);

        // Contract 3: completed
        $contract3 = Contract::create([
            'number'           => 'SHN-2024-003',
            'executor_id'      => $executor->id,
            'supplier'         => 'VitaPlus',
            'start_date'       => '2024-02-01',
            'duration_days'    => 7,
            'expense_category' => 'Dori vositalari',
            'status'           => 'completed',
        ]);
        $item3 = $contract3->items()->create([
            'name'           => 'Vitamin C 1000mg',
            'paid_quantity'  => 200,
            'bonus_quantity' => 10,
            'unit_price'     => '3.00',
        ]);

        // Full delivery for contract3
        $delivery3 = $contract3->deliveries()->create(['delivery_date' => '2024-02-05']);
        DeliveryItem::create([
            'delivery_id'      => $delivery3->id,
            'contract_item_id' => $item3->id,
            'paid_quantity'    => 200,
            'bonus_quantity'   => 10,
        ]);

        $stage3a = PaymentStage::create([
            'contract_id' => $contract3->id,
            'name'        => 'Avans',
            'percentage'  => '30',
            'amount'      => '180.00',
            'sort_order'  => 1,
            'condition'   => 'after_activation',
        ]);
        PaymentStage::create([
            'contract_id' => $contract3->id,
            'name'        => 'Yetkazib berish',
            'percentage'  => '70',
            'amount'      => '420.00',
            'sort_order'  => 2,
            'condition'   => 'after_full_delivery',
        ]);
    }
}
