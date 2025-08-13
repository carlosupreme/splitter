<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class EventInvitation extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'response_message',
        'invited_at',
        'responded_at',
    ];

    protected $casts = [
        'status' => InvitationStatus::class,
        'invited_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', InvitationStatus::PENDING);
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', InvitationStatus::ACCEPTED);
    }

    public function scopeDeclined(Builder $query): Builder
    {
        return $query->where('status', InvitationStatus::DECLINED);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForEvent(Builder $query, int $eventId): Builder
    {
        return $query->where('event_id', $eventId);
    }

    // Helper Methods
    public function accept(string $message = null): bool
    {
        return $this->updateStatus(InvitationStatus::ACCEPTED, $message);
    }

    public function decline(string $message = null): bool
    {
        return $this->updateStatus(InvitationStatus::DECLINED, $message);
    }

    protected function updateStatus(InvitationStatus $status, string $message = null): bool
    {
        $this->status = $status;
        $this->response_message = $message;
        $this->responded_at = now();
        
        return $this->save();
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatus::PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === InvitationStatus::ACCEPTED;
    }

    public function isDeclined(): bool
    {
        return $this->status === InvitationStatus::DECLINED;
    }
}
