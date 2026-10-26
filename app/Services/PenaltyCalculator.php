<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Penalty calculation for delivery contracts.
 *
 * Rules:
 * - Penalty starts from the day AFTER the deadline.
 * - For each overdue day, penalty = remaining_paid_amount * 0.5% (2 decimal places, rounded half-up).
 * - A delivery batch received on date X is counted from X+1 onward (not same day).
 * - Total penalty is capped at 50% of the paid amount that was outstanding on the deadline day.
 * - No float: bcmath used throughout.
 */
class PenaltyCalculator
{
    private const DAILY_RATE = '0.005'; // 0.5%

    private const CAP_RATE = '0.5';   // 50%

    private const SCALE = 2;

    /**
     * @param  string  $totalPaidAmount  Total contract paid amount (e.g. "2500.00")
     * @param  Carbon  $startDate  Contract start date
     * @param  int  $durationDays  Duration in calendar days
     * @param  array<array{date: Carbon, paid: int, unit_price: string}>  $deliveries  Batches received
     * @param  Carbon|null  $asOf  Calculation date (default: today)
     * @return array{
     *     total: string,
     *     overdue_days: int,
     *     limit: string,
     *     daily: list<array{date: string, outstanding: string, daily_penalty: string}>
     * }
     */
    public function calculate(
        string $totalPaidAmount,
        Carbon $startDate,
        int $durationDays,
        array $deliveries,
        ?Carbon $asOf = null,
    ): array {
        $asOf ??= Carbon::today();
        $deadline = $startDate->copy()->addDays($durationDays);

        // Penalty only applies after the deadline
        if (! $asOf->gt($deadline)) {
            return [
                'total' => '0.00',
                'overdue_days' => 0,
                'limit' => '0.00',
                'daily' => [],
            ];
        }

        // Limit = 50% of outstanding at deadline end-of-day
        // "outstanding at deadline" means what had NOT arrived by the deadline date (i.e. not arrived on or before deadline)
        $outstandingAtDeadline = $this->computeOutstanding(
            $totalPaidAmount,
            $deliveries,
            $deadline // deliveries on the deadline date DO count toward reducing it
        );
        $limit = $this->roundHalfUp(bcmul($outstandingAtDeadline, self::CAP_RATE, 10));

        $dailyBreakdown = [];
        $runningTotal = '0.00';

        // Iterate from deadline+1 to asOf (inclusive)
        $day = $deadline->copy()->addDay();
        while (! $day->gt($asOf)) {
            // Outstanding for this day = deliveries arrived BEFORE this day (strictly before)
            // A batch on date X reduces outstanding starting from X+1
            $outstanding = $this->computeOutstanding($totalPaidAmount, $deliveries, $day->copy()->subDay());

            if (bccomp($outstanding, '0', self::SCALE) > 0) {
                $dailyPenalty = $this->roundHalfUp(bcmul($outstanding, self::DAILY_RATE, 10));
            } else {
                $dailyPenalty = '0.00';
            }

            $dailyBreakdown[] = [
                'date' => $day->toDateString(),
                'outstanding' => $outstanding,
                'daily_penalty' => $dailyPenalty,
            ];

            $runningTotal = bcadd($runningTotal, $dailyPenalty, self::SCALE);
            $day->addDay();
        }

        // Apply cap
        if (bccomp($runningTotal, $limit, self::SCALE) > 0) {
            $runningTotal = $limit;
        }

        return [
            'total' => $runningTotal,
            'overdue_days' => count($dailyBreakdown),
            'limit' => $limit,
            'daily' => $dailyBreakdown,
        ];
    }

    /**
     * Compute the outstanding paid amount as of end-of $asOfDate.
     * Deliveries with delivery_date <= $asOfDate reduce the outstanding.
     *
     * @param  array<array{date: Carbon, paid_amount: string}>  $deliveries
     */
    private function computeOutstanding(string $totalPaidAmount, array $deliveries, Carbon $asOfDate): string
    {
        $delivered = '0.00';
        foreach ($deliveries as $delivery) {
            if (! $delivery['date']->gt($asOfDate)) {
                $delivered = bcadd($delivered, $delivery['paid_amount'], 10);
            }
        }

        $outstanding = bcsub($totalPaidAmount, $delivered, 10);

        // Cannot go negative
        if (bccomp($outstanding, '0', 10) < 0) {
            $outstanding = '0';
        }

        return $this->roundHalfUp($outstanding);
    }

    /**
     * Round to 2 decimal places using half-up rule (0.005 and above → up).
     */
    private function roundHalfUp(string $value): string
    {
        // Add 0.005 then truncate to 2 decimal places
        $shifted = bcadd($value, '0.005', 10);
        // Truncate: multiply by 100, floor, divide by 100
        $multiplied = bcmul($shifted, '100', 0);

        return bcdiv($multiplied, '100', 2);
    }
}
