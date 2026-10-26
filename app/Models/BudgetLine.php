<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetLine extends Model
{
    use HasFactory;

    protected $fillable = ['fiscal_year', 'expense_category', 'month', 'allocated_amount'];

    protected function casts(): array
    {
        return ['allocated_amount' => 'string'];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function activePayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('is_voided', false);
    }

    /** Total spent from this budget line — uses bcadd to avoid float precision loss from SQL SUM */
    public function spentAmount(): string
    {
        return $this->activePayments()
            ->pluck('amount')
            ->reduce(fn (string $carry, mixed $amount) => bcadd($carry, (string) $amount, 2), '0.00');
    }

    /** Remaining balance */
    public function remainingAmount(): string
    {
        return bcsub($this->allocated_amount, $this->spentAmount(), 2);
    }
}
