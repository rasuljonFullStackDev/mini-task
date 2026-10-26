<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_stage_id', 'budget_line_id', 'created_by',
        'amount', 'payment_date', 'reference', 'notes',
        'is_voided', 'void_reason', 'voided_by', 'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'voided_at' => 'datetime',
            'amount' => 'string',
            'is_voided' => 'boolean',
        ];
    }

    public function paymentStage(): BelongsTo
    {
        return $this->belongsTo(PaymentStage::class);
    }

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
