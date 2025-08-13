<?php

namespace App\Filament\Resources\EventInvitations\Pages\EventInvitations;

use App\Enums\InvitationStatus;
use App\Filament\Resources\EventInvitations\EventInvitationResource;
use App\Models\EventInvitation;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class MyInvitations extends Page
{
    protected static string $resource = EventInvitationResource::class;

    protected static ?string $title = 'My Event Invitations';

    protected static ?string $navigationLabel = 'My Invitations';

    protected string $view = 'filament.resources.event-invitations.pages.event-invitations.my-invitations';

    public $pendingInvitations = [];

    public $respondedInvitations = [];

    public function mount(): void
    {
        $this->loadInvitations();
    }

    protected function loadInvitations(): void
    {
        $user = auth()->user();

        $this->pendingInvitations = $user->eventInvitations()
            ->with(['event.organizer'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        $this->respondedInvitations = $user->eventInvitations()
            ->with(['event.organizer'])
            ->whereIn('status', [InvitationStatus::ACCEPTED->value, InvitationStatus::DECLINED->value])
            ->orderBy('responded_at', 'desc')
            ->get()
            ->toArray();
    }

    public function acceptInvitation($invitationId, $message = null): void
    {
        try {
            $invitation = EventInvitation::find($invitationId);

            if (! $invitation || $invitation->user_id !== auth()->id()) {
                Notification::make()
                    ->title('Invitation not found')
                    ->danger()
                    ->send();

                return;
            }

            if ($invitation->accept($message)) {
                Notification::make()
                    ->title('Invitation Accepted')
                    ->body("You've successfully accepted the invitation to {$invitation->event->title}")
                    ->success()
                    ->send();

                $this->loadInvitations();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error accepting invitation')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function declineInvitation($invitationId, $message = null): void
    {
        try {
            $invitation = EventInvitation::find($invitationId);

            if (! $invitation || $invitation->user_id !== auth()->id()) {
                Notification::make()
                    ->title('Invitation not found')
                    ->danger()
                    ->send();

                return;
            }

            if ($invitation->decline($message)) {
                Notification::make()
                    ->title('Invitation Declined')
                    ->body("You've declined the invitation to {$invitation->event->title}")
                    ->success()
                    ->send();

                $this->loadInvitations();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error declining invitation')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
