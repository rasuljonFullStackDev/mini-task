<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penalty extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'calculated_at', 'calculated_amount',
        'approved_amount', 'overdue_days', 'limit_amount',
        'status', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'calculated_at' => 'date',
            'reviewed_at' => 'datetime',
            'calculated_amount' => 'string',
            'approved_amount' => 'string',
            'limit_amount' => 'string',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /** The effective amount shown to accounting */
    public function effectiveAmount(): string
    {
        if (! $this->isApproved()) {
            return '0.00';
        }

        return $this->approved_amount ?? $this->calculated_amount;
    }
}
