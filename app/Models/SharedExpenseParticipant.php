<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SharedExpenseParticipant extends Model
{
    protected $fillable = [
        'shared_expense_id',
        'user_id',
        'assigned_amount',
        'paid_amount',
        'status',
    ];

    protected $casts = [
        'assigned_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function sharedExpense(): BelongsTo
    {
        return $this->belongsTo(SharedExpense::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SharedExpensePayment::class);
    }

    public function getRemainingAmount(): float
    {
        return max(0, $this->assigned_amount - $this->paid_amount);
    }

    public function isFullyPaid(): bool
    {
        return $this->paid_amount >= $this->assigned_amount;
    }

    // Update status based on payment amount
    public function updateStatus(): void
    {
        if ($this->paid_amount >= $this->assigned_amount) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'pending';
        }
        $this->save();
    }

    // Auto-update status when payments change
    protected static function booted()
    {
        static::updated(function ($participant) {
            if ($participant->wasChanged('paid_amount')) {
                $participant->updateStatus();
            }
        });
    }
}
