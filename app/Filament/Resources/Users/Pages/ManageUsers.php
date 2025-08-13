<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array {
        return [
            Action::make('friendRequests')
                  ->label('Ver mis solicitudes de amistad')
                  ->url(FriendRequests::getUrl())
                  ->icon('heroicon-o-users')
                  ->color('primary'),
        ];
    }
}
