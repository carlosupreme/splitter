<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\FriendBalance;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class FriendRequests extends Page
{
    protected static string $resource = UserResource::class;

    protected string $view = 'filament.resources.users.pages.friend-requests';

    public $pendingRequests = [];

    public $friends = [];

    public function mount(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        $user = auth()->user();

        // Get pending friend requests received by current user
        $this->pendingRequests = $user->getPendingFriendships()->map(function ($friendship) {
            $sender = User::find($friendship->sender_id);

            return [
                'id' => $friendship->id,
                'sender_id' => $friendship->sender_id,
                'sender_name' => $sender->name ?? 'Unknown User',
                'sender_email' => $sender->email ?? '',
                'created_at' => $friendship->created_at,
                'sender' => $sender,
            ];
        })->toArray();

        // Get current friends
        $this->friends = $user->getFriends()->map(function ($friend) {
            return [
                'id' => $friend->id,
                'name' => $friend->name,
                'email' => $friend->email,
                'created_at' => $friend->created_at,
            ];
        })->toArray();
    }

    public function acceptFriendRequest($senderId): void
    {
        try {
            $sender = User::find($senderId);

            if (! $sender) {
                Notification::make()
                    ->title('User not found')
                    ->danger()
                    ->send();

                return;
            }

            $result = auth()->user()->acceptFriendRequest($sender);

            if ($result) {
                // Create friend balance records for both users (starts at zero)
                FriendBalance::getOrCreateBalance(auth()->id(), $sender->id);
                FriendBalance::getOrCreateBalance($sender->id, auth()->id());

                Notification::make()
                    ->title('Friend request accepted')
                    ->body("You are now friends with {$sender->name}")
                    ->success()
                    ->send();

                $this->loadData(); // Refresh data
            } else {
                Notification::make()
                    ->title('Failed to accept friend request')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error accepting friend request')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function denyFriendRequest($senderId): void
    {
        try {
            $sender = User::find($senderId);

            if (! $sender) {
                Notification::make()
                    ->title('User not found')
                    ->danger()
                    ->send();

                return;
            }

            $result = auth()->user()->denyFriendRequest($sender);

            if ($result) {
                Notification::make()
                    ->title('Friend request denied')
                    ->body("Friend request from {$sender->name} has been denied")
                    ->success()
                    ->send();

                $this->loadData(); // Refresh data
            } else {
                Notification::make()
                    ->title('Failed to deny friend request')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error denying friend request')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function unfriend($friendId): void
    {
        try {
            $friend = User::find($friendId);

            if (! $friend) {
                Notification::make()
                    ->title('User not found')
                    ->danger()
                    ->send();

                return;
            }

            $result = auth()->user()->unfriend($friend);

            if ($result) {
                Notification::make()
                    ->title('Friend removed')
                    ->body("You are no longer friends with {$friend->name}")
                    ->success()
                    ->send();

                $this->loadData(); // Refresh data
            } else {
                Notification::make()
                    ->title('Failed to remove friend')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error removing friend')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
