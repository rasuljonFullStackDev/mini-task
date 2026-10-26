<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractItem extends Model
{
    use HasFactory;

    protected $fillable = ['contract_id', 'name', 'paid_quantity', 'bonus_quantity', 'unit_price'];

    protected function casts(): array
    {
        return ['unit_price' => 'string'];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function deliveryItems(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    /** Total paid amount for this item */
    public function totalPaidAmount(): string
    {
        return bcmul((string) $this->paid_quantity, $this->unit_price, 2);
    }

    /** Already delivered paid quantity (from active deliveries) */
    public function deliveredPaidQuantity(): int
    {
        return (int) $this->deliveryItems()->sum('paid_quantity');
    }

    /** Already delivered bonus quantity */
    public function deliveredBonusQuantity(): int
    {
        return (int) $this->deliveryItems()->sum('bonus_quantity');
    }

    /** Remaining paid quantity to be delivered */
    public function remainingPaidQuantity(): int
    {
        return $this->paid_quantity - $this->deliveredPaidQuantity();
    }

    /** Remaining bonus quantity to be delivered */
    public function remainingBonusQuantity(): int
    {
        return $this->bonus_quantity - $this->deliveredBonusQuantity();
    }
}
