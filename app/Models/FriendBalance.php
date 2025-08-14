<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FriendBalance extends Model
{
    protected $fillable = [
        'user_id',
        'friend_id',
        'balance_amount',
        'total_expenses',
        'total_user_created',
        'total_friend_created',
        'last_activity_at',
    ];

    protected $casts = [
        'balance_amount' => 'decimal:2',
        'total_user_created' => 'decimal:2',
        'total_friend_created' => 'decimal:2',
        'last_activity_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function friend(): BelongsTo
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    // Helper methods
    public function getNetBalance(): float
    {
        return (float) $this->balance_amount;
    }

    public function getUserOwesAmount(): float
    {
        return $this->balance_amount < 0 ? abs((float) $this->balance_amount) : 0.00;
    }

    public function getFriendOwesAmount(): float
    {
        return $this->balance_amount > 0 ? (float) $this->balance_amount : 0.00;
    }

    public function getStatus(): string
    {
        if ($this->balance_amount == 0) {
            return 'Settled';
        } elseif ($this->balance_amount > 0) {
            return 'They owe you';
        } else {
            return 'You owe them';
        }
    }

    // Static methods for balance management
    public static function getOrCreateBalance(int $userId, int $friendId): FriendBalance
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'friend_id' => $friendId,
            ],
            [
                'balance_amount' => 0.00,
                'total_expenses' => 0,
                'total_user_created' => 0.00,
                'total_friend_created' => 0.00,
                'last_activity_at' => now(),
            ]
        );
    }

    public static function updateBalanceForExpense(SharedExpense $expense): void
    {
        // Update balance for each participant
        foreach ($expense->participants as $participant) {
            // Update balance from creator's perspective
            $balance = self::getOrCreateBalance($expense->created_by, $participant->user_id);

            // Recalculate balance from this user's created expenses
            $userCreatedAmount = $participant->assigned_amount - $participant->paid_amount;
            $balance->increment('total_expenses');
            $balance->increment('total_user_created', $userCreatedAmount);

            // Recalculate balance from friend's created expenses
            $friendCreatedAmount = \DB::table('shared_expense_participants')
                ->join('shared_expenses', 'shared_expenses.id', '=', 'shared_expense_participants.shared_expense_id')
                ->where('shared_expenses.created_by', $participant->user_id)
                ->where('shared_expenses.status', 'active')
                ->where('shared_expense_participants.user_id', $expense->created_by)
                ->sum(\DB::raw('assigned_amount - paid_amount')) ?? 0;

            // Update net balance (positive = friend owes user, negative = user owes friend)
            $balance->update([
                'balance_amount' => $userCreatedAmount - $friendCreatedAmount,
                'total_friend_created' => $friendCreatedAmount,
                'last_activity_at' => now(),
            ]);

            // Also update the reverse balance for the friend
            $reverseBalance = self::getOrCreateBalance($participant->user_id, $expense->created_by);
            $reverseBalance->increment('total_expenses');
            $reverseBalance->increment('total_friend_created', $userCreatedAmount);

            // Update reverse balance (flip the sign)
            $reverseBalance->update([
                'balance_amount' => $friendCreatedAmount - $userCreatedAmount,
                'total_user_created' => $friendCreatedAmount,
                'last_activity_at' => now(),
            ]);
        }
    }

    public static function updateBalanceForPayment(SharedExpensePayment $payment): void
    {
        $participant = $payment->participant;
        $expense = $participant->sharedExpense;

        // Update balances for this expense
        self::updateBalanceForExpense($expense);
    }
}
