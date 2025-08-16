<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Models\BudgetItem;
use App\Models\BudgetPayment;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

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
            }, 'creator', 'images'])
            ->get()
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_expense')
                ->label('Agregar Gasto')
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->schema([
                    TextInput::make('title')
                        ->label('Título del Gasto')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Ej: Cena del viernes'),

                    Textarea::make('description')
                        ->label('Descripción')
                        ->maxLength(1000)
                        ->placeholder('Detalles adicionales sobre el gasto (opcional)'),

                    TextInput::make('amount')
                        ->label('Monto')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0.01)
                        ->placeholder('0.00'),

                    Select::make('category')
                        ->label('Categoría')
                        ->options([
                            'general' => 'General',
                            'accommodation' => 'Alojamiento',
                            'transportation' => 'Transporte',
                            'food' => 'Comida y Bebidas',
                            'entertainment' => 'Entretenimiento',
                            'shopping' => 'Compras',
                        ])
                        ->default('general')
                        ->required(),

                    FileUpload::make('photos')
                        ->label('Adjuntar Fotos (Factura, Recibo, etc.)')
                        ->image()
                        ->multiple()
                        ->maxFiles(5)
                        ->directory('budget-images')
                        ->imagePreviewHeight(150)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                        ->maxSize(5120) // 5MB
                        ->helperText('Sube hasta 5 fotos (JPEG, PNG, GIF, WebP) - Máximo 5MB cada una')
                        ->columnSpanFull(),
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

                    // Handle photo uploads
                    if (! empty($data['photos'])) {
                        foreach ($data['photos'] as $photo) {
                            $budgetItem->images()->create([
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
                        ->title('Gasto Agregado Exitosamente')
                        ->body($data['photos'] ? 'Con '.count($data['photos']).' foto(s) adjunta(s)' : '')
                        ->success()
                        ->send();

                    $this->loadBudgetData();
                }),

            Action::make('add_payment')
                ->label('Registrar Pago')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->schema([
                    Select::make('budget_item_id')
                        ->label('Gasto')
                        ->options(function () {
                            $unpaidExpenses = $this->event->expenses()
                                ->with(['creator', 'payments'])
                                ->get()
                                ->filter(function ($expense) {
                                    // Calculate total payments for this expense
                                    $totalPaid = $expense->payments->sum('amount');

                                    // Check if expense is not fully paid
                                    return $totalPaid < $expense->amount;
                                });

                            if ($unpaidExpenses->isEmpty()) {
                                return ['no_expenses' => '¡Todos los gastos están completamente pagados!'];
                            }

                            return $unpaidExpenses->mapWithKeys(fn ($expense) => [
                                $expense->id => "{$expense->title} - \${$expense->amount} (por {$expense->creator->name}) - Restante: \$".number_format($expense->amount - $expense->payments->sum('amount'), 2),
                            ]);
                        })
                        ->required()
                        ->searchable()
                        ->placeholder('Selecciona el gasto al que se aplica este pago')
                        ->helperText('Solo se muestran gastos que no están totalmente pagados')
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state && $state !== 'no_expenses') {
                                $expense = $this->event->expenses()->with('payments')->find($state);
                                if ($expense) {
                                    $totalPaid = $expense->payments->sum('amount');
                                    $remaining = $expense->amount - $totalPaid;
                                    // Set suggested amount to remaining amount
                                    $set('amount', number_format($remaining, 2, '.', ''));
                                }
                            } else {
                                // Clear amount if no valid expense is selected
                                $set('amount', '');
                            }
                        })
                        ->rules([
                            function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    if ($value === 'no_expenses') {
                                        $fail('No hay gastos disponibles para registrar pagos.');
                                    }
                                };
                            },
                        ]),

                    TextInput::make('amount')
                        ->label('Monto del Pago')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0.01)
                        ->placeholder('0.00')
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $budgetItemId = $get('budget_item_id');
                            if ($budgetItemId && $state) {
                                $expense = $this->event->expenses()->with('payments')->find($budgetItemId);
                                if ($expense) {
                                    $totalPaid = $expense->payments->sum('amount');
                                    $remaining = $expense->amount - $totalPaid;
                                    if ($state > $remaining) {
                                        $set('amount', $remaining);
                                    }
                                }
                            }
                        })
                        ->suffixAction(
                            Action::make('fill_remaining')
                                ->icon('heroicon-m-calculator')
                                ->tooltip('Llenar monto restante')
                                ->action(function ($set, $get) {
                                    $budgetItemId = $get('budget_item_id');
                                    if ($budgetItemId && $budgetItemId !== 'no_expenses') {
                                        $expense = $this->event->expenses()->with('payments')
                                            ->find($budgetItemId);
                                        if ($expense) {
                                            $totalPaid = $expense->payments->sum('amount');
                                            $remaining = $expense->amount - $totalPaid;
                                            $set('amount', number_format($remaining, 2, '.', ''));
                                        }
                                    }
                                })
                                ->disabled(function ($get) {
                                    $budgetItemId = $get('budget_item_id');

                                    return ! $budgetItemId || $budgetItemId === 'no_expenses';
                                })
                        )
                        ->helperText('El monto no puede exceder el monto restante del gasto. Usa 🧮 para llenar automáticamente.')
                        ->rules([
                            function ($get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $budgetItemId = $get('budget_item_id');

                                    // Check if no_expenses was selected
                                    if ($budgetItemId === 'no_expenses') {
                                        $fail('No hay gastos disponibles para pagar.');

                                        return;
                                    }

                                    if ($budgetItemId && $value) {
                                        $expense = $this->event->expenses()->with('payments')
                                            ->find($budgetItemId);
                                        if ($expense) {
                                            $totalPaid = $expense->payments->sum('amount');
                                            $remaining = $expense->amount - $totalPaid;
                                            if ($value > $remaining) {
                                                $fail('El monto no puede exceder el monto restante de $'.number_format($remaining, 2));
                                            }
                                        }
                                    }
                                };
                            },
                        ]),

                    Textarea::make('note')
                        ->label('Nota del Pago')
                        ->maxLength(500)
                        ->placeholder('Nota opcional sobre este pago'),

                    DateTimePicker::make('paid_at')
                        ->label('Fecha del Pago')
                        ->default(now())
                        ->required()
                        ->native(false),

                    FileUpload::make('photos')
                        ->label('Adjuntar Fotos (Recibo, Captura de Transferencia, etc.)')
                        ->image()
                        ->multiple()
                        ->maxFiles(3)
                        ->directory('budget-images')
                        ->imagePreviewHeight(150)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                        ->maxSize(5120) // 5MB
                        ->helperText('Sube hasta 3 fotos como comprobante de pago - Máximo 5MB cada una')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    // Prevent payment creation if no valid expense is selected
                    if ($data['budget_item_id'] === 'no_expenses') {
                        Notification::make()
                            ->title('Error')
                            ->body('No hay gastos disponibles para registrar pagos.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $payment = BudgetPayment::create([
                        'budget_item_id' => $data['budget_item_id'],
                        'paid_by' => auth()->id(),
                        'amount' => $data['amount'],
                        'note' => $data['note'],
                        'paid_at' => $data['paid_at'],
                    ]);

                    // Handle photo uploads
                    if (! empty($data['photos'])) {
                        foreach ($data['photos'] as $photo) {
                            $payment->images()->create([
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
                        ->title('Pago Registrado Exitosamente')
                        ->body($data['photos'] ? 'Con '.count($data['photos']).' foto(s) adjunta(s) como comprobante' : '')
                        ->success()
                        ->send();

                    $this->loadBudgetData();
                }),
        ];
    }

    public function getTitle(): string
    {
        return "Presupuesto - {$this->event->title}";
    }
}
