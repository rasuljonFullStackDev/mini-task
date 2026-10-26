<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'name', 'percentage', 'amount',
        'sort_order', 'condition', 'due_date',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'string',
            'percentage' => 'string',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function activePayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('is_voided', false);
    }

    /** Total paid (non-voided) — uses bcadd to avoid float precision loss from SQL SUM */
    public function paidAmount(): string
    {
        return $this->activePayments()
            ->pluck('amount')
            ->reduce(fn (string $carry, mixed $amount) => bcadd($carry, (string) $amount, 2), '0.00');
    }

    /** Remaining to pay */
    public function remainingAmount(): string
    {
        return bcsub($this->amount, $this->paidAmount(), 2);
    }

    /**
     * Computed status based on payment registry.
     * Never stored manually.
     */
    public function computedStatus(): string
    {
        $paid = $this->paidAmount();
        $amount = $this->amount;
        $today = now()->toDateString();

        if (bccomp($paid, '0', 2) === 0) {
            if ($this->due_date && $this->due_date->toDateString() < $today) {
                // Check if condition is met; if so it's overdue, if not it's scheduled
                if ($this->isConditionMet()) {
                    return 'overdue';
                }
            }

            return 'scheduled';
        }

        if (bccomp($paid, $amount, 2) >= 0) {
            return 'paid';
        }

        if ($this->due_date && $this->due_date->toDateString() < $today) {
            return 'overdue';
        }

        return 'partial';
    }

    /** Check if the payment condition is met for this stage */
    public function isConditionMet(): bool
    {
        if ($this->condition === 'after_activation') {
            return $this->contract->isActive() || $this->contract->isCompleted();
        }

        if ($this->condition === 'after_full_delivery') {
            return $this->contract->isCompleted();
        }

        return false;
    }
}
