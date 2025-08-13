<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Models\BudgetItem;
use App\Models\BudgetPayment;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class EventBudget extends Page
{
    use InteractsWithRecord;

    protected static string $resource = EventResource::class;

    protected string $view = 'filament.resources.events.pages.event-budget';

    public Event $event;

    public array $budgetSummary = [];

    public array $userSplits = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->event = $this->record;

        // Check authorization
        if (! $this->event->canUserAccessBudget(auth()->id())) {
            abort(403, 'You do not have access to this event budget.');
        }

        $this->loadBudgetData();
    }

    protected function loadBudgetData(): void
    {
        $this->budgetSummary = $this->event->getBudgetSummaryForUser(auth()->id());

        // Load user splits for all expenses
        $this->userSplits = $this->event->expenses()
            ->with(['splits' => function ($query) {
                $query->where('user_id', auth()->id());
            }, 'creator'])
            ->get()
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_expense')
                ->label('Add Expense')
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->maxLength(1000),

                    TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0.01),

                    Select::make('category')
                        ->options([
                            'general' => 'General',
                            'accommodation' => 'Accommodation',
                            'transportation' => 'Transportation',
                            'food' => 'Food & Drinks',
                            'entertainment' => 'Entertainment',
                            'shopping' => 'Shopping',
                        ])
                        ->default('general')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $budgetItem = BudgetItem::create([
                        'event_id' => $this->event->id,
                        'created_by' => auth()->id(),
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'type' => 'expense',
                        'amount' => $data['amount'],
                        'category' => $data['category'],
                        'split_equally' => true,
                    ]);

                    $budgetItem->createSplits();

                    Notification::make()
                        ->title('Expense Added Successfully')
                        ->success()
                        ->send();

                    $this->loadBudgetData();
                }),

            Action::make('add_payment')
                ->label('Record Payment')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->schema([
                    Select::make('budget_item_id')
                        ->label('Expense Item')
                        ->options(
                            $this->event->expenses()
                                ->with('creator')
                                ->get()
                                ->mapWithKeys(fn ($expense) => [
                                    $expense->id => "{$expense->title} - \${$expense->amount} (by {$expense->creator->name})",
                                ])
                        )
                        ->required()
                        ->searchable(),

                    TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0.01),

                    Textarea::make('note')
                        ->label('Payment Note')
                        ->maxLength(500)
                        ->placeholder('Optional note about this payment'),

                    DateTimePicker::make('paid_at')
                        ->label('Payment Date')
                        ->default(now())
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    BudgetPayment::create([
                        'budget_item_id' => $data['budget_item_id'],
                        'paid_by' => auth()->id(),
                        'amount' => $data['amount'],
                        'note' => $data['note'],
                        'paid_at' => $data['paid_at'],
                    ]);

                    Notification::make()
                        ->title('Payment Recorded Successfully')
                        ->success()
                        ->send();

                    $this->loadBudgetData();
                }),
        ];
    }

    public function getTitle(): string
    {
        return "Budget - {$this->event->title}";
    }
}
