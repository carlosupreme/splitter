<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetSplit extends Model
{
    protected $fillable = [
        'budget_item_id',
        'user_id',
        'share_amount',
        'paid_amount',
        'status',
    ];

    protected $casts = [
        'share_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRemainingAmount(): float
    {
        return max(0, $this->share_amount - $this->paid_amount);
    }

    public function getOverpaidAmount(): float
    {
        return max(0, $this->paid_amount - $this->share_amount);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPartial(): bool
    {
        return $this->status === 'partial';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverpaid(): bool
    {
        return $this->status === 'overpaid';
    }
}
