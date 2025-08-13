<?php

namespace App\Filament\Resources\EventInvitations;

use App\Enums\InvitationStatus;
use App\Filament\Resources\EventInvitations\Pages\EventInvitations\MyInvitations;
use App\Filament\Resources\EventInvitations\Pages\ManageEventInvitations;
use App\Models\EventInvitation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventInvitationResource extends Resource
{
    protected static ?string $model = EventInvitation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Event Invitations';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'declined' => 'Declined',
                    ])
                    ->required()
                    ->disabled(),

                Textarea::make('response_message')
                    ->label('Response Message')
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', auth()->id())
                ->with(['event.organizer'])
            )
            ->columns([
                TextColumn::make('event.title')
                    ->label('Event')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event.category')
                    ->label('Category')
                    ->badge()
                    ->colors([
                        'primary' => 'general',
                        'success' => 'party',
                        'warning' => 'meeting',
                        'danger' => 'trip',
                        'info' => 'dinner',
                        'gray' => 'conference',
                        'purple' => 'sports',
                    ]),

                TextColumn::make('event.organizer.name')
                    ->label('Organizer')
                    ->searchable(),

                TextColumn::make('event.start_date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => InvitationStatus::PENDING->value,
                        'success' => InvitationStatus::ACCEPTED->value,
                        'danger' => InvitationStatus::DECLINED->value,
                    ]),

                TextColumn::make('invited_at')
                    ->label('Invited')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
            ])
            ->recordActions([
                Action::make('accept')
                    ->label('Accept')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(static fn (EventInvitation $record) => $record->status === InvitationStatus::PENDING)
                    ->schema([
                        Textarea::make('response_message')
                            ->label('Optional Message')
                            ->maxLength(500),
                    ])
                    ->action(function (EventInvitation $record, array $data) {
                        $record->accept($data['response_message'] ?? null);
                        Notification::make()
                            ->title('Invitation Accepted')
                            ->success()
                            ->send();
                    }),

                Action::make('decline')
                    ->label('Decline')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (EventInvitation $record) => $record->status === InvitationStatus::PENDING)
                    ->schema([
                        Textarea::make('response_message')
                            ->label('Optional Message')
                            ->maxLength(500),
                    ])
                    ->action(function (EventInvitation $record, array $data) {
                        $record->decline($data['response_message'] ?? null);
                        Notification::make()
                            ->title('Invitation Declined')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([])
            ->defaultSort('invited_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEventInvitations::route('/'),
            'my-invitations' => MyInvitations::route('/my-invitations'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
