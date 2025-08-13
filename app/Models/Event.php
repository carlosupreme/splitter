<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'location',
        'start_date',
        'end_date',
        'max_attendees',
        'organizer_id',
        'category',
        'is_public',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_public' => 'boolean',
    ];

    // Relationships
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(EventInvitation::class);
    }

    public function invitees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_invitations')
            ->withPivot(['status', 'response_message', 'invited_at', 'responded_at'])
            ->withTimestamps();
    }

    public function acceptedInvitees(): BelongsToMany
    {
        return $this->invitees()->wherePivot('status', 'accepted');
    }

    public function pendingInvitees(): BelongsToMany
    {
        return $this->invitees()->wherePivot('status', 'pending');
    }

    // Scopes
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_date', '>', now());
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('start_date', '<', now());
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeOrganizedBy(Builder $query, int $userId): Builder
    {
        return $query->where('organizer_id', $userId);
    }

    // Helper Methods
    public function inviteFriends(array $friendIds): void
    {
        $invitations = [];
        foreach ($friendIds as $friendId) {
            $invitations[] = [
                'event_id' => $this->id,
                'user_id' => $friendId,
                'status' => InvitationStatus::PENDING->value,
                'invited_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        EventInvitation::insert($invitations);
    }

    public function getAttendeeCount(): int
    {
        return $this->acceptedInvitees()->count();
    }

    public function hasSpace(): bool
    {
        if (!$this->max_attendees) {
            return true;
        }

        return $this->getAttendeeCount() < $this->max_attendees;
    }

    public function isUserInvited(int $userId): bool
    {
        return $this->invitees()->where('user_id', $userId)->exists();
    }

    public function getUserInvitationStatus(int $userId): ?InvitationStatus
    {
        $invitation = $this->invitations()->where('user_id', $userId)->first();

        return $invitation ? InvitationStatus::from($invitation->status) : null;
    }
}
