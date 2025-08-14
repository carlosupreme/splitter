<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\InvitationStatus;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Multicaret\Acquaintances\Traits\Friendable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Friendable, HasFactory,
        Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'photo',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Event Relationships
    public function organizedEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }

    public function eventInvitations(): HasMany
    {
        return $this->hasMany(EventInvitation::class);
    }

    public function invitedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_invitations')
            ->withPivot(['status', 'response_message', 'invited_at', 'responded_at'])
            ->withTimestamps();
    }

    // Event Helper Methods
    public function createEvent(array $data, array $inviteeFriends = []): Event
    {
        $event = $this->organizedEvents()->create($data);

        if (! empty($inviteeFriends)) {
            $event->inviteFriends($inviteeFriends);
        }

        return $event;
    }

    public function respondToInvitation(Event $event, InvitationStatus $status, ?string $message = null): bool
    {
        $invitation = $this->eventInvitations()
            ->where('event_id', $event->id)
            ->first();

        if (! $invitation || ! $invitation->isPending()) {
            return false;
        }

        return match ($status) {
            InvitationStatus::ACCEPTED => $invitation->accept($message),
            InvitationStatus::DECLINED => $invitation->decline($message),
            default => false,
        };
    }

    public function getPendingEventInvitations()
    {
        return $this->eventInvitations()
            ->with(['event.organizer'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAvailableFriendsForEvent(Event $event)
    {
        $invitedFriendIds = $event->invitees()->pluck('users.id')->toArray();

        return $this->getFriends()->reject(function ($friend) use ($invitedFriendIds) {
            return in_array($friend->id, $invitedFriendIds);
        });
    }

    // SharedExpense Relationships
    public function sharedExpensesCreated(): HasMany
    {
        return $this->hasMany(SharedExpense::class, 'created_by');
    }

    public function sharedExpenseParticipations(): HasMany
    {
        return $this->hasMany(SharedExpenseParticipant::class, 'user_id');
    }

    // FriendBalance Relationships
    public function friendBalances(): HasMany
    {
        return $this->hasMany(FriendBalance::class, 'user_id');
    }

    public function getFriendBalance(int $friendId): ?FriendBalance
    {
        return $this->friendBalances()->where('friend_id', $friendId)->first();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
