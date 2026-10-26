<?php

namespace App\Services;

use App\Models\BudgetLine;
use App\Models\Payment;
use App\Models\PaymentStage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Validate and record a payment.
     *
     * Checks are performed in this exact order, stopping at the first failure:
     *  1. Contract must be Active or Completed.
     *  2. Stage condition must be satisfied.
     *  3. All previous stages must be fully paid.
     *  4. Amount must be > 0 and ≤ remaining stage amount.
     *  5. Payment date matches fiscal year of budget line, and amount ≤ budget line remaining.
     *
     * @param  array{
     *     payment_stage_id: int,
     *     budget_line_id: int,
     *     amount: string,
     *     payment_date: string,
     *     reference: ?string,
     *     notes: ?string,
     * } $data
     *
     * @throws \RuntimeException with a user-friendly message on the first failed check
     */
    public function record(array $data, User $createdBy): Payment
    {
        return DB::transaction(function () use ($data, $createdBy) {
            /** @var PaymentStage $stage */
            $stage = PaymentStage::with('contract.paymentStages')->lockForUpdate()->findOrFail($data['payment_stage_id']);
            $contract = $stage->contract;

            // 1. Contract status
            if (! $contract->isActive() && ! $contract->isCompleted()) {
                throw new \RuntimeException('Shartnoma Faol yoki Yakunlangan holatda bo\'lishi kerak.');
            }

            // 2. Stage condition
            if (! $stage->isConditionMet()) {
                $conditionLabel = $stage->condition === 'after_full_delivery'
                    ? 'tovar to\'liq yetkazilmagan'
                    : 'shartnoma faollashtirmagan';
                throw new \RuntimeException("To'lov sharti bajarilmagan: {$conditionLabel}.");
            }

            // 3. Previous stages fully paid
            foreach ($contract->paymentStages as $previousStage) {
                if ($previousStage->sort_order >= $stage->sort_order) {
                    break;
                }
                if (bccomp($previousStage->remainingAmount(), '0', 2) > 0) {
                    throw new \RuntimeException(
                        "\"{$previousStage->name}\" bosqichi hali to'liq to'lanmagan."
                    );
                }
            }

            // 4. Amount validity
            $amount = $data['amount'];
            if (bccomp($amount, '0', 2) <= 0) {
                throw new \RuntimeException('To\'lov summasi noldan katta bo\'lishi kerak.');
            }
            $remaining = $stage->remainingAmount();
            if (bccomp($amount, $remaining, 2) > 0) {
                throw new \RuntimeException(
                    "Summa bosqich qoldig'idan ({$remaining}) oshmasligi kerak."
                );
            }

            // 5. Budget line check
            /** @var BudgetLine $budgetLine */
            $budgetLine = BudgetLine::lockForUpdate()->findOrFail($data['budget_line_id']);
            $paymentDate = Carbon::parse($data['payment_date']);

            if ($paymentDate->year !== $budgetLine->fiscal_year) {
                throw new \RuntimeException(
                    "To'lov sanasi smeta moliyaviy yiliga ({$budgetLine->fiscal_year}) mos emas."
                );
            }

            $budgetRemaining = $budgetLine->remainingAmount();
            if (bccomp($amount, $budgetRemaining, 2) > 0) {
                throw new \RuntimeException(
                    "Smeta qoldig'i yetarli emas. Qoldiq: {$budgetRemaining}."
                );
            }

            return Payment::create([
                'payment_stage_id' => $stage->id,
                'budget_line_id' => $budgetLine->id,
                'created_by' => $createdBy->id,
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_voided' => false,
            ]);
        });
    }

    /**
     * Void a payment (never delete).
     *
     * @throws \RuntimeException if already voided
     */
    public function void(Payment $payment, string $reason, User $voidedBy): Payment
    {
        if ($payment->is_voided) {
            throw new \RuntimeException('Bu to\'lov allaqachon bekor qilingan.');
        }

        $payment->update([
            'is_voided' => true,
            'void_reason' => $reason,
            'voided_by' => $voidedBy->id,
            'voided_at' => now(),
        ]);

        return $payment->fresh();
    }
}
