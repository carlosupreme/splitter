<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEvents extends ManageRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth('7xl')
                ->mutateDataUsing(function (array $data): array {
                    $data['organizer_id'] = auth()->id();

                    return $data;
                })
                ->after(function (Event $record, array $data): void {
                    // Handle friend invitations after event creation
                    if (! empty($data['invite_friends'])) {
                        $record->inviteFriends($data['invite_friends']);
                    }
                }),
        ];
    }
}
