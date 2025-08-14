<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SharedExpensePayment extends Model
{
    protected $fillable = [
        'shared_expense_participant_id',
        'paid_by',
        'amount',
        'note',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(SharedExpenseParticipant::class, 'shared_expense_participant_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(BudgetImage::class, 'imageable');
    }

    // Auto-update participant's paid_amount when payment is created/updated/deleted
    protected static function booted()
    {
        static::created(function ($payment) {
            $payment->updateParticipantPaidAmount();
            // Update friend balances when payment is made
            \App\Models\FriendBalance::updateBalanceForPayment($payment);
        });

        static::updated(function ($payment) {
            $payment->updateParticipantPaidAmount();
            // Update friend balances when payment is updated
            \App\Models\FriendBalance::updateBalanceForPayment($payment);
        });

        static::deleted(function ($payment) {
            $payment->updateParticipantPaidAmount();
            // Update friend balances when payment is deleted
            \App\Models\FriendBalance::updateBalanceForPayment($payment);
        });
    }

    public function updateParticipantPaidAmount(): void
    {
        $participant = $this->participant;
        $totalPaid = $participant->payments()->sum('amount');
        $participant->update(['paid_amount' => $totalPaid]);
    }
}
