<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SharedExpense extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'description',
        'total_amount',
        'category',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SharedExpenseParticipant::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(BudgetImage::class, 'imageable');
    }

    // Calculate total amount paid so far
    public function getTotalPaid(): float
    {
        return $this->participants()->sum('paid_amount');
    }

    // Calculate remaining amount
    public function getRemainingAmount(): float
    {
        return $this->total_amount - $this->getTotalPaid();
    }

    // Check if expense is fully settled
    public function isSettled(): bool
    {
        return $this->getRemainingAmount() <= 0;
    }

    // Get participants who still owe money
    public function getPendingParticipants()
    {
        return $this->participants()
            ->whereColumn('paid_amount', '<', 'assigned_amount')
            ->with('user')
            ->get();
    }

    // Check if user can access this expense (creator or participant)
    public function canUserAccess(int $userId): bool
    {
        if ($this->created_by === $userId) {
            return true;
        }

        return $this->participants()->where('user_id', $userId)->exists();
    }

    // Model events for automatic balance updates
    protected static function booted()
    {
        static::created(function ($expense) {
            // Update friend balances when a new expense is created
            \App\Models\FriendBalance::updateBalanceForExpense($expense);
        });

        static::updated(function ($expense) {
            // Update friend balances when an expense is updated
            \App\Models\FriendBalance::updateBalanceForExpense($expense);
        });

        static::deleted(function ($expense) {
            // Update friend balances when an expense is deleted
            \App\Models\FriendBalance::updateBalanceForExpense($expense);
        });
    }
}
