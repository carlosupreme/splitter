<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\Events\Pages\Events\MyEvents;
use App\Filament\Resources\Events\Pages\ManageEvents;
use App\Models\Event;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'My Events';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema {
        return $schema
            ->components([
                TextInput::make('title')
                         ->required()
                         ->maxLength(255)
                         ->columnSpanFull(),

                Textarea::make('description')
                        ->maxLength(1000)
                        ->columnSpanFull(),

                TextInput::make('location')
                         ->maxLength(255)
                         ->columnSpan(1),

                Select::make('category')
                      ->options([
                          'party'      => 'Party',
                          'trip'       => 'Trip',
                          'dinner'     => 'Dinner',
                          'meeting'    => 'Meeting',
                          'conference' => 'Conference',
                          'sports'     => 'Sports',
                          'general'    => 'General',
                      ])
                      ->default('general')
                      ->required()
                      ->native(false)
                      ->columnSpan(1),

                DateTimePicker::make('start_date')
                              ->required()
                              ->columnSpan(1)
                              ->native(false),

                DateTimePicker::make('end_date')
                              ->columnSpan(1)
                              ->native(false),

                TextInput::make('max_attendees')
                         ->numeric()
                         ->minValue(1)
                         ->columnSpan(1),

                Toggle::make('is_public')
                      ->label('Public Event')
                      ->default(false)
                      ->columnSpan(1),

                Select::make('invite_friends')
                      ->label('Invite Friends')
                      ->multiple()
                      ->options(function () {
                          return auth()->user()->getFriends()->pluck('name', 'id');
                      })
                      ->preload()
                      ->columnSpanFull()
                      ->helperText('Select friends to invite to this event')
            ])
            ->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
                Section::make('Event Details')
                    ->schema([
                        TextEntry::make('title')
                                 ->size(Size::Large->value),

                        TextEntry::make('description')
                                 ->columnSpanFull(),

                        TextEntry::make('category')
                                 ->badge()
                                 ->colors([
                                     'primary' => 'general',
                                     'success' => 'party',
                                     'warning' => 'meeting',
                                     'danger'  => 'trip',
                                     'info'    => 'dinner',
                                     'gray'    => 'conference',
                                     'purple'  => 'sports',
                                 ]),

                        TextEntry::make('location'),

                        TextEntry::make('start_date')
                                 ->label('Start Date')
                                 ->dateTime(),

                        TextEntry::make('end_date')
                                 ->label('End Date')
                                 ->dateTime(),

                        TextEntry::make('max_attendees')
                                 ->label('Max Attendees'),

                        TextEntry::make('is_public')
                                 ->label('Public Event')
                                 ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                 ->badge()
                                 ->colors([
                                     'success' => true,
                                     'gray' => false,
                                 ]),
                    ])
                    ->columns(2),

                Section::make('Attendees')
                    ->schema([
                        RepeatableEntry::make('acceptedInvitees')
                                      ->label('')
                                      ->schema([
                                          TextEntry::make('name')
                                                   ->label('Name'),
                                          TextEntry::make('email')
                                                   ->label('Email'),
                                          TextEntry::make('pivot.responded_at')
                                                   ->label('Accepted At')
                                                   ->dateTime(),
                                      ])
                                      ->columns(3)
                                      ->columnSpanFull(),
                    ]),

                Section::make('All Invitations')
                    ->schema([
                        RepeatableEntry::make('invitations')
                                      ->label('')
                                      ->schema([
                                          TextEntry::make('user.name')
                                                   ->label('Name'),
                                          TextEntry::make('user.email')
                                                   ->label('Email'),
                                          TextEntry::make('status')
                                                   ->badge()
                                                   ->colors([
                                                       'warning' => 'pending',
                                                       'success' => 'accepted',
                                                       'danger' => 'declined',
                                                   ]),
                                          TextEntry::make('invited_at')
                                                   ->label('Invited At')
                                                   ->dateTime(),
                                          TextEntry::make('responded_at')
                                                   ->label('Responded At')
                                                   ->dateTime(),
                                      ])
                                      ->columns(5)
                                      ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('organizer_id', auth()->id())
                                                           ->withCount(['acceptedInvitees', 'invitations']))
            ->columns([
                TextColumn::make('title')
                          ->searchable()
                          ->sortable(),

                TextColumn::make('category')
                          ->badge()
                          ->colors([
                              'primary' => 'general',
                              'success' => 'party',
                              'warning' => 'meeting',
                              'danger'  => 'trip',
                              'info'    => 'dinner',
                              'gray'    => 'conference',
                              'purple'  => 'sports',
                          ]),

                TextColumn::make('location')
                          ->searchable()
                          ->limit(30),

                TextColumn::make('start_date')
                          ->label('Date')
                          ->dateTime()
                          ->sortable(),

                TextColumn::make('accepted_invitees_count')
                          ->label('Attendees')
                          ->badge()
                          ->color('success')
                          ->formatStateUsing(fn ($state) => $state ?: '0'),

                TextColumn::make('invitations_count')
                          ->label('Total Invitations')
                          ->counts('invitations')
                          ->badge()
                          ->color('info'),

                TextColumn::make('is_public')
                          ->label('Public')
                          ->badge()
                          ->formatStateUsing(fn(string $state): string => $state ? 'Public' : 'Private')
                          ->colors([
                              'success' => true,
                              'gray'    => false,
                          ]),
            ])
            ->filters([
                SelectFilter::make('category')
                            ->options([
                                'party'      => 'Party',
                                'trip'       => 'Trip',
                                'dinner'     => 'Dinner',
                                'meeting'    => 'Meeting',
                                'conference' => 'Conference',
                                'sports'     => 'Sports',
                                'general'    => 'General',
                            ]),

            ])
            ->recordActions([
                ViewAction::make()->slideOver()->modalWidth('7xl'),
                EditAction::make()->slideOver()->modalWidth('7xl'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array {
        return [
            'index'     => ManageEvents::route('/'),
//            'my-events' => MyEvents::route('/me'),
        ];
    }

}
