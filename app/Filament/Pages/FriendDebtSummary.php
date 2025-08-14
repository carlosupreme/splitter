<?php

namespace App\Filament\Pages;

use App\Models\FriendBalance;
use App\Models\SharedExpense;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class FriendDebtSummary extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected string $view = 'filament.pages.friend-debt-summary';

    protected static ?string $navigationLabel = 'Friend Balances';

    protected static ?string $title = 'Friend Debt Summary';

    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('friend_name')
                    ->label('Friend')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_expenses')
                    ->label('Total Expenses')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('i_owe_them')
                    ->label('I Owe Them')
                    ->money('USD')
                    ->color('danger')
                    ->getStateUsing(fn ($record) => max(0, -$record->balance_amount)),

                TextColumn::make('they_owe_me')
                    ->label('They Owe Me')
                    ->money('USD')
                    ->color('success')
                    ->getStateUsing(fn ($record) => max(0, $record->balance_amount)),

                TextColumn::make('balance_amount')
                    ->label('Net Balance')
                    ->badge()
                    ->colors([
                        'success' => fn ($state): bool => $state > 0,
                        'danger' => fn ($state): bool => $state < 0,
                        'gray' => fn ($state): bool => $state == 0,
                    ])
                    ->formatStateUsing(function ($state) {
                        if ($state > 0) {
                            return '+$'.number_format($state, 2);
                        } elseif ($state < 0) {
                            return '-$'.number_format(abs($state), 2);
                        } else {
                            return 'Settled';
                        }
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->balance_amount == 0) {
                            return 'Settled';
                        } elseif ($record->balance_amount > 0) {
                            return 'They owe you';
                        } else {
                            return 'You owe them';
                        }
                    })
                    ->colors([
                        'success' => 'They owe you',
                        'danger' => 'You owe them',
                        'gray' => 'Settled',
                    ]),

                TextColumn::make('last_activity_at')
                    ->label('Last Activity')
                    ->dateTime(),
            ])
            ->filters([
                // Note: Complex filters on computed columns are not feasible with current approach
                // Users can use table search and sort instead
            ])
            ->recordActions([
                Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-m-eye')
                    ->slideOver()
                    ->modalContent(fn ($record) => $this->getDetailView($record))
                    ->modalWidth('7xl'),
            ])
            ->defaultSort('friend_name', 'asc')
            ->emptyStateHeading('No Friend Expenses Found')
            ->emptyStateDescription('Start sharing expenses with friends to see balances here.')
            ->emptyStateIcon('heroicon-o-users');
    }

    protected function getTableQuery(): Builder
    {
        $userId = auth()->id();

        // Use the persistent FriendBalance records for much faster queries
        return FriendBalance::query()
            ->select([
                'friend_balances.id',
                'friend_balances.friend_id as id',
                'users.name as friend_name',
                'users.email as friend_email',
                'friend_balances.balance_amount',
                'friend_balances.total_expenses',
                'friend_balances.total_user_created',
                'friend_balances.total_friend_created',
                'friend_balances.last_activity_at',
            ])
            ->join('users', 'users.id', '=', 'friend_balances.friend_id')
            ->where('friend_balances.user_id', $userId)
            ->whereNotNull('users.email_verified_at');
    }

    protected function getDetailView($record): View
    {
        $friendId = $record->id;
        $userId = auth()->id();

        // Get detailed breakdown of all expenses between these two users
        $expensesICreated = SharedExpense::where('created_by', $userId)
            ->where('status', 'active')
            ->whereHas('participants', function ($query) use ($friendId) {
                $query->where('user_id', $friendId);
            })
            ->with(['participants' => function ($query) use ($friendId) {
                $query->where('user_id', $friendId);
            }])
            ->get();

        $expensesTheyCreated = SharedExpense::where('created_by', $friendId)
            ->where('status', 'active')
            ->whereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['participants' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->get();

        // Add computed fields for the detail view (use persistent balance data)
        $record->they_owe_me = max(0, $record->balance_amount);
        $record->i_owe_them = max(0, -$record->balance_amount);
        $record->net_balance = $record->balance_amount;

        return view('filament.pages.friend-debt-details', [
            'friend' => $record,
            'expensesICreated' => $expensesICreated,
            'expensesTheyCreated' => $expensesTheyCreated,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Balances')
                ->icon('heroicon-m-arrow-path')
                ->action(fn () => $this->resetTable()),
        ];
    }
}
