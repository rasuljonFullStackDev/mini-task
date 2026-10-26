<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function create(Contract $contract): View
    {
        $this->authorize('addDelivery', $contract);
        $contract->load('items');

        return view('deliveries.create', compact('contract'));
    }

    public function store(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('addDelivery', $contract);
        $contract->load('items');

        $data = $request->validate([
            'delivery_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.contract_item_id' => 'required|integer|exists:contract_items,id',
            'items.*.paid_quantity' => 'required|integer|min:0',
            'items.*.bonus_quantity' => 'nullable|integer|min:0',
        ]);

        // Validate quantities don't exceed remaining
        foreach ($data['items'] as $itemData) {
            $contractItem = $contract->items->firstWhere('id', $itemData['contract_item_id']);
            if (! $contractItem) {
                return back()->withErrors(['Noto\'g\'ri tovar qatori.']);
            }

            $paidQty = (int) ($itemData['paid_quantity'] ?? 0);
            $bonusQty = (int) ($itemData['bonus_quantity'] ?? 0);

            if ($paidQty > $contractItem->remainingPaidQuantity()) {
                return back()->withErrors([
                    "\"{$contractItem->name}\": pullik qoldiq {$contractItem->remainingPaidQuantity()}, kiritilgan {$paidQty}.",
                ]);
            }
            if ($bonusQty > $contractItem->remainingBonusQuantity()) {
                return back()->withErrors([
                    "\"{$contractItem->name}\": bonus qoldiq {$contractItem->remainingBonusQuantity()}, kiritilgan {$bonusQty}.",
                ]);
            }
        }

        DB::transaction(function () use ($contract, $data) {
            $delivery = $contract->deliveries()->create([
                'delivery_date' => $data['delivery_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $itemData) {
                $paidQty = (int) ($itemData['paid_quantity'] ?? 0);
                $bonusQty = (int) ($itemData['bonus_quantity'] ?? 0);
                if ($paidQty === 0 && $bonusQty === 0) {
                    continue;
                }
                $delivery->items()->create([
                    'contract_item_id' => $itemData['contract_item_id'],
                    'paid_quantity' => $paidQty,
                    'bonus_quantity' => $bonusQty,
                ]);
            }

            // Auto-complete contract if all paid items are delivered
            $allDelivered = $contract->items->every(fn ($item) => $item->remainingPaidQuantity() === 0);
            if ($allDelivered && $contract->isActive()) {
                $contract->update(['status' => 'completed']);
            }
        });

        return redirect()->route('contracts.show', $contract)->with('success', 'Partiya kiritildi.');
    }
}
