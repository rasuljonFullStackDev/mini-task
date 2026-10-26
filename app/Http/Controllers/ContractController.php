<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\PaymentStage;
use App\Services\PenaltyCalculator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $query = Contract::with('executor');

        if ($user->isExecutor()) {
            $query->where('executor_id', $user->id);
        }

        $contracts = $query->latest()->paginate(20);

        return view('contracts.index', compact('contracts'));
    }

    public function create(): View
    {
        $this->authorize('create', Contract::class);

        return view('contracts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Contract::class);

        $data = $request->validate([
            'number' => 'required|string|unique:contracts,number',
            'supplier' => 'required|string|max:255',
            'start_date' => 'required|date',
            'duration_days' => 'required|integer|min:1',
            'expense_category' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.paid_quantity' => 'required|integer|min:1',
            'items.*.bonus_quantity' => 'nullable|integer|min:0',
            'items.*.unit_price' => 'required|numeric|min:0.01',
        ]);

        $contract = Contract::create([
            'number' => $data['number'],
            'executor_id' => Auth::id(),
            'supplier' => $data['supplier'],
            'start_date' => $data['start_date'],
            'duration_days' => $data['duration_days'],
            'expense_category' => $data['expense_category'],
            'status' => 'draft',
        ]);

        foreach ($data['items'] as $item) {
            $contract->items()->create([
                'name' => $item['name'],
                'paid_quantity' => $item['paid_quantity'],
                'bonus_quantity' => $item['bonus_quantity'] ?? 0,
                'unit_price' => bcadd((string) $item['unit_price'], '0', 2),
            ]);
        }

        return redirect()->route('contracts.show', $contract)->with('success', 'Shartnoma yaratildi.');
    }

    public function show(Contract $contract, PenaltyCalculator $penaltyCalc): View
    {
        $this->authorize('view', $contract);
        $contract->load('items', 'deliveries.items.contractItem', 'penalties', 'paymentStages.payments');

        $asOf = request('as_of') ? Carbon::parse(request('as_of')) : Carbon::today();
        $penaltyData = $this->buildPenaltyData($contract, $penaltyCalc, $asOf);

        return view('contracts.show', compact('contract', 'penaltyData', 'asOf'));
    }

    public function activate(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('activate', $contract);

        if ($contract->items()->count() === 0) {
            return back()->withErrors(['Faollashtirish uchun kamida 1 ta tovar qatori kerak.']);
        }

        $contract->update(['status' => 'active']);

        // Create default payment stages
        $totalPaid = $contract->totalPaidAmount();
        $stages = [
            ['name' => 'Avans', 'percentage' => '30', 'condition' => 'after_activation', 'sort_order' => 1],
            ['name' => 'Yetkazib berish', 'percentage' => '70', 'condition' => 'after_full_delivery', 'sort_order' => 2],
        ];

        foreach ($stages as $stage) {
            $amount = $this->roundHalfUp(bcmul($totalPaid, bcdiv($stage['percentage'], '100', 10), 10));
            PaymentStage::create([
                'contract_id' => $contract->id,
                'name' => $stage['name'],
                'percentage' => $stage['percentage'],
                'amount' => $amount,
                'sort_order' => $stage['sort_order'],
                'condition' => $stage['condition'],
            ]);
        }

        return redirect()->route('contracts.show', $contract)->with('success', 'Shartnoma faollashtirildi.');
    }

    public function edit(Contract $contract): View
    {
        $this->authorize('update', $contract);
        $contract->load('items');

        return view('contracts.edit', compact('contract'));
    }

    public function update(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('update', $contract);

        $data = $request->validate([
            'supplier' => 'required|string|max:255',
            'start_date' => 'required|date',
            'duration_days' => 'required|integer|min:1',
            'expense_category' => 'required|string|max:255',
        ]);

        $contract->update($data);

        return redirect()->route('contracts.show', $contract)->with('success', 'Shartnoma yangilandi.');
    }

    private function buildPenaltyData(Contract $contract, PenaltyCalculator $calc, Carbon $asOf): array
    {
        $deliveries = $contract->deliveries->flatMap(function ($delivery) {
            return $delivery->items->map(fn ($item) => [
                'date' => $delivery->delivery_date,
                'paid_amount' => bcmul((string) $item->paid_quantity, $item->contractItem->unit_price, 2),
            ]);
        })->groupBy(fn ($d) => $d['date']->toDateString())->map(function ($group) {
            $total = array_reduce($group->all(), fn ($c, $d) => bcadd($c, $d['paid_amount'], 2), '0.00');

            return ['date' => $group->first()['date'], 'paid_amount' => $total];
        })->values()->all();

        return $calc->calculate(
            $contract->totalPaidAmount(),
            $contract->start_date,
            $contract->duration_days,
            $deliveries,
            $asOf,
        );
    }

    private function roundHalfUp(string $value): string
    {
        $shifted = bcadd($value, '0.005', 10);
        $multiplied = bcmul($shifted, '100', 0);

        return bcdiv($multiplied, '100', 2);
    }
}
