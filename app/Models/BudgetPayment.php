<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BudgetPayment extends Model
{
    protected $fillable = [
        'budget_item_id',
        'paid_by',
        'amount',
        'note',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(BudgetImage::class, 'imageable');
    }

    // Update the corresponding split when a payment is made
    protected static function booted()
    {
        static::created(function ($payment) {
            $payment->updateSplit();
        });

        static::updated(function ($payment) {
            $payment->updateSplit();
        });

        static::deleted(function ($payment) {
            $payment->updateSplit();
        });
    }

    public function updateSplit(): void
    {
        $split = BudgetSplit::where('budget_item_id', $this->budget_item_id)
            ->where('user_id', $this->paid_by)
            ->first();

        if ($split) {
            $totalPaid = BudgetPayment::where('budget_item_id', $this->budget_item_id)
                ->where('paid_by', $this->paid_by)
                ->sum('amount');

            $split->paid_amount = $totalPaid;

            if ($split->paid_amount >= $split->share_amount) {
                $split->status = $split->paid_amount > $split->share_amount ? 'overpaid' : 'paid';
            } elseif ($split->paid_amount > 0) {
                $split->status = 'partial';
            } else {
                $split->status = 'pending';
            }

            $split->save();
        }
    }
}
