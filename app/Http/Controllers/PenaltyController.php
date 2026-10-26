<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Penalty;
use App\Services\PenaltyCalculator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PenaltyController extends Controller
{
    public function store(Request $request, Contract $contract, PenaltyCalculator $calc): RedirectResponse
    {
        $this->authorize('view', $contract);

        $data = $request->validate([
            'as_of' => 'required|date',
        ]);

        $asOf = Carbon::parse($data['as_of']);
        $contract->load('items', 'deliveries.items.contractItem');

        $deliveries = $this->buildDeliveries($contract);
        $result = $calc->calculate($contract->totalPaidAmount(), $contract->start_date, $contract->duration_days, $deliveries, $asOf);

        if (bccomp($result['total'], '0', 2) <= 0) {
            return back()->withErrors(['Jarima yo\'q (kechikish aniqlanmadi).']);
        }

        // Replace existing pending penalty
        $contract->penalties()->where('status', 'pending')->delete();

        $contract->penalties()->create([
            'calculated_at' => $asOf,
            'calculated_amount' => $result['total'],
            'overdue_days' => $result['overdue_days'],
            'limit_amount' => $result['limit'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Jarima hisoblandi va boshliqqa yuborildi.');
    }

    public function review(Request $request, Penalty $penalty): RedirectResponse
    {
        $this->authorize('review', $penalty);

        $data = $request->validate([
            'action' => 'required|in:approve,reject',
            'approved_amount' => 'nullable|numeric|min:0',
            'review_notes' => 'nullable|string',
        ]);

        $status = $data['action'] === 'approve' ? 'approved' : 'rejected';
        $approvedAmount = null;

        if ($status === 'approved') {
            $approvedAmount = $data['approved_amount'] !== null
                ? bcadd((string) $data['approved_amount'], '0', 2)
                : $penalty->calculated_amount;
        }

        $penalty->update([
            'status' => $status,
            'approved_amount' => $approvedAmount,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
        ]);

        $msg = $status === 'approved' ? 'Jarima tasdiqlandi.' : 'Jarima rad etildi.';

        return back()->with('success', $msg);
    }

    private function buildDeliveries(Contract $contract): array
    {
        return $contract->deliveries->flatMap(function ($delivery) {
            return $delivery->items->map(fn ($item) => [
                'date' => $delivery->delivery_date,
                'paid_amount' => bcmul((string) $item->paid_quantity, $item->contractItem->unit_price, 2),
            ]);
        })->groupBy(fn ($d) => $d['date']->toDateString())->map(function ($group) {
            $total = array_reduce($group->all(), fn ($c, $d) => bcadd($c, $d['paid_amount'], 2), '0.00');

            return ['date' => $group->first()['date'], 'paid_amount' => $total];
        })->values()->all();
    }
}
