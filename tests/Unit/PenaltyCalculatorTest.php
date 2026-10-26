<?php

namespace Tests\Unit;

use App\Services\PenaltyCalculator;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PenaltyCalculatorTest extends TestCase
{
    private PenaltyCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new PenaltyCalculator;
    }

    #[Test]
    public function example1_nothing_delivered_three_days_overdue(): void
    {
        $result = $this->calc->calculate(
            '2500.00',
            Carbon::parse('2024-03-02'),
            10,
            [],
            Carbon::parse('2024-03-15')
        );

        $this->assertSame('37.50', $result['total']);
        $this->assertSame(3, $result['overdue_days']);
        $this->assertSame('1250.00', $result['limit']);
    }

    #[Test]
    public function example2_partial_delivery_on_day14(): void
    {
        $result = $this->calc->calculate(
            '2500.00',
            Carbon::parse('2024-03-02'),
            10,
            [['date' => Carbon::parse('2024-03-14'), 'paid_amount' => '1500.00']],
            Carbon::parse('2024-03-16')
        );

        $this->assertSame('35.00', $result['total']);
        $this->assertSame(4, $result['overdue_days']);
        $this->assertSame('1250.00', $result['limit']);
    }

    #[Test]
    public function no_penalty_before_deadline(): void
    {
        $result = $this->calc->calculate(
            '2500.00',
            Carbon::parse('2024-03-02'),
            10,
            [],
            Carbon::parse('2024-03-12')
        );

        $this->assertSame('0.00', $result['total']);
        $this->assertSame(0, $result['overdue_days']);
    }

    #[Test]
    public function penalty_capped_at_50_percent_of_outstanding_at_deadline(): void
    {
        $result = $this->calc->calculate(
            '2500.00',
            Carbon::parse('2024-03-02'),
            10,
            [],
            Carbon::parse('2024-09-30')
        );

        $this->assertSame('1250.00', $result['total']);
        $this->assertSame('1250.00', $result['limit']);
    }

    #[Test]
    public function delivery_on_deadline_date_reduces_from_next_day(): void
    {
        $result = $this->calc->calculate(
            '2500.00',
            Carbon::parse('2024-03-02'),
            10,
            [['date' => Carbon::parse('2024-03-12'), 'paid_amount' => '2500.00']],
            Carbon::parse('2024-03-15')
        );

        $this->assertSame('0.00', $result['total']);
    }

    #[Test]
    public function delivery_day_after_deadline_reduces_outstanding(): void
    {
        $result = $this->calc->calculate(
            '2500.00',
            Carbon::parse('2024-03-02'),
            10,
            [['date' => Carbon::parse('2024-03-13'), 'paid_amount' => '2500.00']],
            Carbon::parse('2024-03-15')
        );

        $this->assertSame('12.50', $result['total']);
    }

    #[Test]
    public function rounding_half_up_applied_per_day(): void
    {
        $result = $this->calc->calculate(
            '1.00',
            Carbon::parse('2024-03-02'),
            10,
            [],
            Carbon::parse('2024-03-14')
        );

        $this->assertSame('0.02', $result['total']);
    }
}
