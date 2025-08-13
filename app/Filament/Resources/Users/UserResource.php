<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\FriendRequests;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema {
        return $schema
            ->components([
                TextInput::make('name')
                         ->required(),
                TextInput::make('email')
                         ->label('Email address')
                         ->email()
                         ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                         ->password()
                         ->required(),
                TextInput::make('photo'),
                TextInput::make('phone')
                         ->tel(),
            ]);
    }

    public static function infolist(Schema $schema): Schema {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email')
                         ->label('Email address'),
                TextEntry::make('email_verified_at')
                         ->dateTime(),
                TextEntry::make('created_at')
                         ->dateTime(),
                TextEntry::make('updated_at')
                         ->dateTime(),
                TextEntry::make('photo'),
                TextEntry::make('phone'),
            ]);
    }

    public static function table(Table $table): Table {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                          ->searchable(),
                TextColumn::make('email')
                          ->label('Email address')
                          ->searchable(),
                TextColumn::make('email_verified_at')
                          ->dateTime()
                          ->sortable(),
                TextColumn::make('created_at')
                          ->dateTime()
                          ->sortable()
                          ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                          ->dateTime()
                          ->sortable()
                          ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('photo')
                          ->searchable(),
                TextColumn::make('phone')
                          ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('sendFriendRequest')
                      ->label('Send Friend Request')
                      ->icon(Heroicon::OutlinedUserPlus)
                      ->visible(static fn(User $record) => !(auth()
                              ->user()
                              ->isFriendWith($record) || $record
                              ->hasFriendRequestFrom(auth()->user()))
                      )
                      ->action(fn(User $record) => auth()->user()->befriend($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->modifyQueryUsing(static fn($query) => $query->whereNot('id', auth()->id()));
    }

    public static function getPages(): array {
        return [
            'index' => ManageUsers::route('/'),
            'solictudes' => FriendRequests::route('/solicitudes-de-amistad')
        ];
    }
}
