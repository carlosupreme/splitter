<?php

namespace App\Filament\Resources\SharedExpenses;

use App\Filament\Resources\SharedExpenses\Pages\ManageSharedExpenses;
use App\Models\BudgetImage;
use App\Models\SharedExpense;
use App\Models\SharedExpensePayment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Storage;

class SharedExpenseResource extends Resource
{
    protected static ?string $model = SharedExpense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Friend Expenses';

    protected static ?string $modelLabel = 'Friend Expense';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Lunch at Restaurant'),

                Textarea::make('description')
                    ->maxLength(1000)
                    ->placeholder('Optional details about the expense')
                    ->columnSpanFull(),

                TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0.01)
                    ->placeholder('0.00'),

                Select::make('category')
                    ->options([
                        'general' => 'General',
                        'food' => 'Food & Drinks',
                        'transportation' => 'Transportation',
                        'entertainment' => 'Entertainment',
                        'shopping' => 'Shopping',
                        'utilities' => 'Utilities',
                        'accommodation' => 'Accommodation',
                    ])
                    ->default('general')
                    ->required(),

                FileUpload::make('photos')
                    ->label('Attach Photos (Receipt, Invoice, etc.)')
                    ->image()
                    ->multiple()
                    ->maxFiles(5)
                    ->directory('shared-expense-images')
                    ->imagePreviewHeight(150)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                    ->maxSize(5120) // 5MB
                    ->helperText('Upload up to 5 photos as proof - Max 5MB each')
                    ->columnSpanFull(),

                Repeater::make('participants')
                    ->label('Select Friends & Set Amounts')
                    ->relationship('participants')
                    ->schema([
                        Select::make('user_id')
                            ->label('Friend')
                            ->options(function () {
                                return auth()->user()->getFriends()
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->placeholder('Select a friend')
                            ->preload(),

                        TextInput::make('assigned_amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0.01)
                            ->placeholder('0.00'),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->maxItems(10)
                    ->addActionLabel('Add Friend')
                    ->columnSpanFull()
                    ->helperText('Add friends and specify how much each person should pay')
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                        $data['paid_amount'] = 0;
                        $data['status'] = 'pending';

                        return $data;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                $q->where('created_by', auth()->id()) // Expenses I created
                    ->orWhereHas('participants', function ($participantQuery) {
                        $participantQuery->where('user_id', auth()->id());
                    }); // Expenses I'm part of
            })->with(['creator', 'participants.user']))
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('category')
                    ->badge()
                    ->colors([
                        'secondary' => 'general',
                        'warning' => 'food',
                        'info' => 'transportation',
                        'success' => 'entertainment',
                        'danger' => 'utilities',
                    ]),

                TextColumn::make('participants_count')
                    ->label('Friends')
                    ->getStateUsing(fn (SharedExpense $record): int => $record->participants->count())
                    ->badge()
                    ->color('gray'),

                TextColumn::make('my_status')
                    ->label('Your Status')
                    ->getStateUsing(function (SharedExpense $record): string {
                        if ($record->created_by === auth()->id()) {
                            return 'Creator';
                        }

                        $participant = $record->participants()
                            ->where('user_id', auth()->id())
                            ->first();

                        if (! $participant) {
                            return 'Not participating';
                        }

                        $remaining = $participant->getRemainingAmount();
                        if ($remaining <= 0) {
                            return 'Paid';
                        } elseif ($participant->paid_amount > 0) {
                            return 'Partial ($'.number_format($remaining, 2).' left)';
                        } else {
                            return 'Pending ($'.number_format($participant->assigned_amount, 2).' owed)';
                        }
                    })
                    ->badge()
                    ->colors([
                        'primary' => fn (string $state): bool => str_contains($state, 'Creator'),
                        'success' => fn (string $state): bool => str_contains($state, 'Paid'),
                        'warning' => fn (string $state): bool => str_contains($state, 'Partial'),
                        'danger' => fn (string $state): bool => str_contains($state, 'Pending'),
                        'gray' => fn (string $state): bool => str_contains($state, 'Not participating'),
                    ]),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'active',
                        'success' => 'settled',
                        'danger' => 'cancelled',
                    ]),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('make_payment')
                    ->label('Make Payment')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(function (SharedExpense $record): bool {
                        // Only show if user is a participant with pending/partial payments
                        $participant = $record->participants()
                            ->where('user_id', auth()->id())
                            ->first();

                        return $participant && $participant->status !== 'paid';
                    })
                    ->schema([
                        TextInput::make('amount')
                            ->label('Payment Amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0.01)
                            ->placeholder('0.00')
                            ->helperText(function (SharedExpense $record): string {
                                $participant = $record->participants()
                                    ->where('user_id', auth()->id())
                                    ->first();

                                if ($participant) {
                                    $remaining = $participant->getRemainingAmount();

                                    return "You still owe: \${$remaining}";
                                }

                                return '';
                            }),

                        Textarea::make('note')
                            ->label('Payment Note')
                            ->maxLength(500)
                            ->placeholder('Optional note about this payment'),

                        DateTimePicker::make('paid_at')
                            ->label('Payment Date')
                            ->default(now())
                            ->required()
                            ->native(false),

                        FileUpload::make('photos')
                            ->label('Attach Payment Proof')
                            ->image()
                            ->multiple()
                            ->maxFiles(3)
                            ->disk('public')
                            ->directory('shared-expense-images')
                            ->imagePreviewHeight(150)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                            ->maxSize(5120) // 5MB
                            ->helperText('Upload proof of payment - Max 5MB each')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, SharedExpense $record): void {
                        $participant = $record->participants()
                            ->where('user_id', auth()->id())
                            ->first();

                        if (! $participant) {
                            Notification::make()
                                ->title('Error')
                                ->body('You are not a participant in this expense.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Create the payment
                        $payment = SharedExpensePayment::create([
                            'shared_expense_participant_id' => $participant->id,
                            'paid_by' => auth()->id(),
                            'amount' => $data['amount'],
                            'note' => $data['note'],
                            'paid_at' => $data['paid_at'],
                        ]);

                        // Handle photo uploads
                        if (! empty($data['photos'])) {
                            foreach ($data['photos'] as $photo) {
                                BudgetImage::create([
                                    'imageable_type' => SharedExpensePayment::class,
                                    'imageable_id' => $payment->id,
                                    'filename' => basename($photo),
                                    'original_name' => basename($photo),
                                    'mime_type' => Storage::mimeType($photo),
                                    'size' => Storage::size($photo),
                                    'path' => $photo,
                                    'uploaded_by' => auth()->id(),
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Payment Recorded Successfully')
                            ->body('Your payment has been recorded for '.$record->title)
                            ->success()
                            ->send();
                    }),

                EditAction::make()
                    ->slideOver()
                    ->modalWidth('4xl')
                    ->visible(fn (SharedExpense $record): bool => $record->created_by === auth()->id())
                    ->mutateDataUsing(function (SharedExpense $record, array $data): array {
                        // Load existing photos for display
                        $existingPhotos = $record->images()->pluck('path')->toArray();
                        if (! empty($existingPhotos)) {
                            $data['photos'] = $existingPhotos;
                        }

                        return $data;
                    })
                    ->after(function (SharedExpense $record, array $data): void {
                        // Handle photo uploads for edit
                        if (! empty($data['photos'])) {
                            // Remove old images first
                            foreach ($record->images as $image) {
                                $image->delete();
                            }

                            // Add new images
                            foreach ($data['photos'] as $photo) {
                                BudgetImage::create([
                                    'imageable_type' => SharedExpense::class,
                                    'imageable_id' => $record->id,
                                    'filename' => basename($photo),
                                    'original_name' => basename($photo),
                                    'mime_type' => Storage::mimeType($photo),
                                    'size' => Storage::size($photo),
                                    'path' => $photo,
                                    'uploaded_by' => auth()->id(),
                                ]);
                            }
                        }
                    }),
                DeleteAction::make()
                    ->visible(fn (SharedExpense $record): bool => $record->created_by === auth()->id()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSharedExpenses::route('/'),
        ];
    }
}
