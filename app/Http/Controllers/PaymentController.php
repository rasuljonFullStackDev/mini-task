<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $data = $request->validate([
            'payment_stage_id' => 'required|integer|exists:payment_stages,id',
            'budget_line_id' => 'required|integer|exists:budget_lines,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $data['amount'] = bcadd((string) $data['amount'], '0', 2);

        try {
            $this->paymentService->record($data, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('success', 'To\'lov kiritildi.');
    }

    public function void(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('void', $payment);

        $data = $request->validate([
            'void_reason' => 'required|string|min:3',
        ]);

        try {
            $this->paymentService->void($payment, $data['void_reason'], auth()->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['void' => $e->getMessage()]);
        }

        return back()->with('success', 'To\'lov bekor qilindi.');
    }
}
