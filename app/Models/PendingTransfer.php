<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingTransfer extends Model
{
    protected $fillable = [
        'event_id',
        'from_user_id',
        'to_user_id',
        'amount',
        'status',
        'note',
        'requested_at',
        'confirmed_at',
        'confirmed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function canBeConfirmedBy(int $userId): bool
    {
        // Only the creditor (to_user) can confirm the transfer
        return $this->to_user_id === $userId && $this->isPending();
    }

    public function canBeRequestedBy(int $userId): bool
    {
        // Only the debtor (from_user) can request the transfer
        return $this->from_user_id === $userId;
    }
}