<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'executor_id', 'supplier', 'start_date',
        'duration_days', 'status', 'expense_category',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
        ];
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class);
    }

    public function paymentStages(): HasMany
    {
        return $this->hasMany(PaymentStage::class)->orderBy('sort_order');
    }

    public function deadlineDate(): Carbon
    {
        return $this->start_date->copy()->addDays($this->duration_days);
    }

    /** Total paid amount (sum of paid_quantity * unit_price across all items) */
    public function totalPaidAmount(): string
    {
        return $this->items->reduce(function (string $carry, ContractItem $item): string {
            return bcadd($carry, bcmul((string) $item->paid_quantity, $item->unit_price, 2), 2);
        }, '0.00');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
