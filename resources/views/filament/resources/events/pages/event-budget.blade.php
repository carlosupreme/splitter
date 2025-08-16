<x-filament-panels::page>
    <div class="space-y-4 lg:space-y-6">
        {{-- Budget Summary Card --}}
        <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 lg:p-6">
                <h3 class="text-lg lg:text-xl font-semibold text-gray-900 dark:text-white mb-4">💰 Tu Resumen de Presupuesto</h3>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-3 lg:p-4 rounded-lg">
                        <div class="text-xs lg:text-sm font-medium text-blue-600 dark:text-blue-400">Total que Debes</div>
                        <div class="text-lg lg:text-2xl font-bold text-blue-900 dark:text-blue-100">${{ number_format($budgetSummary['total_owed'], 2) }}</div>
                    </div>

                    <div class="bg-green-50 dark:bg-green-900/20 p-3 lg:p-4 rounded-lg">
                        <div class="text-xs lg:text-sm font-medium text-green-600 dark:text-green-400">Total que Pagaste</div>
                        <div class="text-lg lg:text-2xl font-bold text-green-900 dark:text-green-100">${{ number_format($budgetSummary['total_paid'], 2) }}</div>
                    </div>

                    <div class="bg-red-50 dark:bg-red-900/20 p-3 lg:p-4 rounded-lg">
                        <div class="text-xs lg:text-sm font-medium text-red-600 dark:text-red-400">Falta por Pagar</div>
                        <div class="text-lg lg:text-2xl font-bold text-red-900 dark:text-red-100">${{ number_format($budgetSummary['remaining_to_pay'], 2) }}</div>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 lg:p-4 rounded-lg">
                        <div class="text-xs lg:text-sm font-medium text-yellow-600 dark:text-yellow-400">Crédito a Recibir</div>
                        <div class="text-lg lg:text-2xl font-bold text-yellow-900 dark:text-yellow-100">${{ number_format($budgetSummary['credits_to_receive'], 2) }}</div>
                    </div>
                </div>

                <div class="mt-4 flex justify-center">
                    @if($budgetSummary['status'] === 'completed')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                            ✅ Todos los Pagos Completos
                        </span>
                    @elseif($budgetSummary['status'] === 'partial')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                            ⏳ Pagos Parciales Realizados
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                            ❌ Pagos Pendientes
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Event Totals --}}
        <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 lg:p-6">
                <h3 class="text-lg lg:text-xl font-semibold text-gray-900 dark:text-white mb-4">📊 Totales del Evento</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center bg-red-50 dark:bg-red-900/10 p-4 rounded-lg">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Gastos</div>
                        <div class="text-xl lg:text-2xl font-bold text-red-600 dark:text-red-400">${{ number_format($event->getTotalExpenses(), 2) }}</div>
                    </div>

                    <div class="text-center bg-green-50 dark:bg-green-900/10 p-4 rounded-lg">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pagado</div>
                        <div class="text-xl lg:text-2xl font-bold text-green-600 dark:text-green-400">${{ number_format($event->getTotalIncomes(), 2) }}</div>
                    </div>

                    <div class="text-center bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Balance Neto</div>
                        @php $netBalance = $event->getTotalIncomes() - $event->getTotalExpenses(); @endphp
                        <div class="text-xl lg:text-2xl font-bold {{ $netBalance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            ${{ number_format(abs($netBalance), 2) }} {{ $netBalance >= 0 ? 'superávit' : 'déficit' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Debt Overview with Avatars --}}
        @php
            // Get all attendees (organizer + accepted invitees)
            $attendees = collect([$event->organizer])->merge($event->acceptedInvitees);

            // Calculate overall debt status for each attendee
            $attendeeStatus = [];
            foreach ($attendees as $attendee) {
                $summary = $event->getBudgetSummaryForUser($attendee->id);
                $netAmount = $summary['total_paid'] - $summary['total_owed'];

                $attendeeStatus[] = [
                    'user' => $attendee,
                    'net_amount' => $netAmount,
                    'status' => $netAmount > 0 ? 'creditor' : ($netAmount < 0 ? 'debtor' : 'balanced'),
                    'amount' => abs($netAmount),
                    'summary' => $summary
                ];
            }

            // Sort by status: creditors first, then balanced, then debtors
            $attendeeStatus = collect($attendeeStatus)->sortBy(function ($item) {
                return match ($item['status']) {
                    'creditor' => 0,
                    'balanced' => 1,
                    'debtor' => 2,
                };
            });
        @endphp

        <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 lg:p-6">
                <h3 class="text-lg lg:text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    👥 Quién Debe Qué
                </h3>

                <div class="flex flex-wrap justify-center items-center gap-4 lg:gap-6 py-4">
                    @foreach($attendeeStatus as $person)
                        <div class="relative flex flex-col items-center group">
                            {{-- Avatar with status ring --}}
                            <div class="relative">
                                <div class="w-12 h-12 lg:w-16 lg:h-16 rounded-full overflow-hidden border-4 {{
                                    $person['status'] === 'creditor' ? 'border-green-400 shadow-green-400/30' :
                                    ($person['status'] === 'debtor' ? 'border-red-400 shadow-red-400/30' : 'border-gray-300 shadow-gray-300/30')
                                }} shadow-lg">
                                    @if($person['user']->photo)
                                        <img src="{{ Storage::url($person['user']->photo) }}"
                                             alt="{{ $person['user']->name }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center">
                                            <span class="text-white font-bold text-sm lg:text-lg">
                                                {{ strtoupper(substr($person['user']->name, 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Status indicator badge --}}
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 lg:w-6 lg:h-6 rounded-full border-2 border-white dark:border-gray-900 flex items-center justify-center text-xs font-bold {{
                                    $person['status'] === 'creditor' ? 'bg-green-500 text-white' :
                                    ($person['status'] === 'debtor' ? 'bg-red-500 text-white' : 'bg-gray-400 text-white')
                                }}">
                                    {{
                                        $person['status'] === 'creditor' ? '+' :
                                        ($person['status'] === 'debtor' ? '-' : '✓')
                                    }}
                                </div>
                            </div>

                            {{-- Name and amount --}}
                            <div class="mt-2 text-center max-w-20 lg:max-w-24">
                                <div class="font-medium text-xs lg:text-sm text-gray-900 dark:text-white {{ $person['user']->id === auth()->id() ? 'underline decoration-blue-500' : '' }} truncate">
                                    {{ $person['user']->name }}{{ $person['user']->id === auth()->id() ? ' (Tú)' : '' }}
                                </div>

                                @if($person['amount'] > 0)
                                    <div class="text-xs font-medium mt-1 {{
                                        $person['status'] === 'creditor' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                                    }}">
                                        {{ $person['status'] === 'creditor' ? 'Le deben' : 'Debe' }} ${{ number_format($person['amount'], 2) }}
                                    </div>
                                @else
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Al día ✓
                                    </div>
                                @endif
                            </div>

                            {{-- Hover tooltip --}}
                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-10">
                                <div class="bg-black text-white text-xs rounded-lg px-3 py-2 whitespace-nowrap">
                                    <div class="font-medium">{{ $person['user']->name }}</div>
                                    <div>Debe: ${{ number_format($person['summary']['total_owed'], 2) }}</div>
                                    <div>Pagó: ${{ number_format($person['summary']['total_paid'], 2) }}</div>
                                    <div class="{{ $person['net_amount'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                        Neto: {{ $person['net_amount'] >= 0 ? '+' : '' }}${{ number_format($person['net_amount'], 2) }}
                                    </div>
                                    {{-- Arrow --}}
                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-black"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Legend --}}
                <div class="mt-6 flex justify-center">
                    <div class="flex flex-col lg:flex-row items-center gap-3 lg:gap-6 text-xs lg:text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 lg:w-4 lg:h-4 rounded-full bg-green-500 border-2 border-green-400"></div>
                            <span class="text-gray-600 dark:text-gray-400">Debe recibir dinero</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 lg:w-4 lg:h-4 rounded-full bg-red-500 border-2 border-red-400"></div>
                            <span class="text-gray-600 dark:text-gray-400">Debe pagar dinero</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 lg:w-4 lg:h-4 rounded-full bg-gray-400 border-2 border-gray-300"></div>
                            <span class="text-gray-600 dark:text-gray-400">Al día</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            // Get all attendees for filter dropdown
            $allAttendees = collect([$event->organizer])->merge($event->acceptedInvitees)->sortBy('name');
            
            // Get all categories used in this event
            $allCategories = $event->expenses->pluck('category')->unique()->filter()->sort();
            
            $pendingExpenses = $event->expenses()
                ->with(['creator', 'payments', 'splits' => function ($query) {
                    $query->where('user_id', auth()->id());
                }])
                ->orderBy('created_at', 'desc')
                ->get()
                ->filter(function ($expense) {
                    $totalPaid = $expense->payments->sum('amount');
                    return $totalPaid < $expense->amount;
                });

            $allPayments = $event->budgetItems()
                ->where('type', 'expense')
                ->with(['payments.payer', 'payments.images', 'creator'])
                ->get()
                ->flatMap(function ($expense) {
                    return $expense->payments->map(function ($payment) use ($expense) {
                        $payment->expense = $expense;
                        return $payment;
                    });
                })
                ->sortByDesc('paid_at');
        @endphp

        <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            {{-- Advanced Filters Section --}}
            <div class="border-b border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800">
                <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">🔍 Filtros:</span>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 flex-1">
                        {{-- Attendee Filter --}}
                        <div>
                            <select id="filter-attendee" class="w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500" onchange="applyFilters()">
                                <option value="">👥 Todos los Asistentes</option>
                                @foreach($allAttendees as $attendee)
                                    <option value="{{ $attendee->id }}">{{ $attendee->name }}{{ $attendee->id === auth()->id() ? ' (Tú)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Category Filter --}}
                        <div>
                            <select id="filter-category" class="w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500" onchange="applyFilters()">
                                <option value="">🏷️ Todas las Categorías</option>
                                @foreach($allCategories as $category)
                                    <option value="{{ $category }}">{{ ucfirst($category) }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Amount Range Filter --}}
                        <div>
                            <select id="filter-amount" class="w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500" onchange="applyFilters()">
                                <option value="">💰 Todos los Montos</option>
                                <option value="0-50">$0 - $50</option>
                                <option value="50-100">$50 - $100</option>
                                <option value="100-200">$100 - $200</option>
                                <option value="200-500">$200 - $500</option>
                                <option value="500+">$500+</option>
                            </select>
                        </div>

                        {{-- Date Range Filter --}}
                        <div>
                            <select id="filter-date" class="w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500" onchange="applyFilters()">
                                <option value="">📅 Todas las Fechas</option>
                                <option value="today">Hoy</option>
                                <option value="week">Esta Semana</option>
                                <option value="month">Este Mes</option>
                                <option value="older">Más Antiguos</option>
                            </select>
                        </div>
                    </div>

                    {{-- Clear Filters Button --}}
                    <div class="flex items-center gap-2">
                        <button onclick="clearAllFilters()" class="px-3 py-2 text-sm bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                            🗑️ Limpiar
                        </button>
                        <span id="filter-count" class="text-xs text-gray-500 dark:text-gray-400 hidden">
                            <!-- Filter count will be shown here -->
                        </span>
                    </div>
                </div>
            </div>

            {{-- Mobile Tab Selector - Only visible on mobile --}}
            <div class="lg:hidden border-b border-gray-200 dark:border-gray-700 p-4">
                <select id="mobile-tab-selector" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500" onchange="showTabFromSelect(this.value)">
                    <option value="pending">⏳ Pagos Pendientes (@if($pendingExpenses->count() > 0){{ $pendingExpenses->count() }}@endif)</option>
                    <option value="expenses">🧾 Todos los Gastos ({{ $event->expenses->count() }})</option>
                    <option value="payments">💳 Registro de Pagos ({{ $allPayments->count() }})</option>
                </select>
            </div>

            {{-- Desktop Tab Navigation - Hidden on mobile --}}
            <div class="hidden lg:block border-b border-gray-200 dark:border-gray-700">
                <div class="overflow-x-auto">
                    <nav class="flex space-x-4 lg:space-x-8 px-4 lg:px-6 min-w-max" aria-label="Tabs">
                        <button onclick="showTab('pending')" id="pending-tab" class="tab-button flex-shrink-0 py-4 px-3 lg:px-1 border-b-2 font-medium text-sm whitespace-nowrap border-red-500 text-red-600 dark:text-red-400">
                            ⏳ Pagos Pendientes
                            @if($pendingExpenses->count() > 0)
                                <span class="ml-2 px-2 py-1 bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100 rounded-full text-xs font-medium">
                                    {{ $pendingExpenses->count() }}
                                </span>
                            @endif
                        </button>
                        <button onclick="showTab('expenses')" id="expenses-tab" class="tab-button flex-shrink-0 py-4 px-3 lg:px-1 border-b-2 font-medium text-sm whitespace-nowrap border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                            🧾 Todos los Gastos
                            <span class="ml-2 px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100 rounded-full text-xs font-medium">
                                {{ $event->expenses->count() }}
                            </span>
                        </button>
                        <button onclick="showTab('payments')" id="payments-tab" class="tab-button flex-shrink-0 py-4 px-3 lg:px-1 border-b-2 font-medium text-sm whitespace-nowrap border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                            💳 Registro de Pagos
                            <span class="ml-2 px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100 rounded-full text-xs font-medium">
                                {{ $allPayments->count() }}
                            </span>
                        </button>
                    </nav>
                </div>
            </div>

            {{-- Tab Content --}}
            <div class="p-4 lg:p-6">
                {{-- Pending Payments Tab --}}
                <div id="pending-content" class="tab-content">
                    @if($pendingExpenses->count() > 0)
                        <div class="space-y-4">
                            @foreach($pendingExpenses as $expense)
                                @php
                                    $totalPaid = $expense->payments->sum('amount');
                                    $remaining = $expense->amount - $totalPaid;
                                    $userSplit = $expense->splits->first();
                                    $attendeeCount = $expense->splits->count();
                                @endphp

                                <div class="filterable-expense border-l-4 border-red-400 bg-red-50 dark:bg-red-900/10 border border-gray-200 dark:border-gray-700 rounded-lg p-3 lg:p-4" 
                                     data-creator-id="{{ $expense->creator->id }}" 
                                     data-category="{{ $expense->category }}" 
                                     data-amount="{{ $expense->amount }}" 
                                     data-created-date="{{ $expense->created_at->format('Y-m-d') }}">
                                    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-3">
                                        <div class="flex-1">
                                            <div class="flex flex-col lg:flex-row lg:items-center gap-2">
                                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $expense->title }}</h4>
                                                <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full w-fit">
                                                    {{ ucfirst($expense->category) }}
                                                </span>
                                            </div>

                                            @if($expense->description)
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $expense->description }}</p>
                                            @endif

                                            <div class="text-xs lg:text-sm text-gray-500 dark:text-gray-500 mt-1">
                                                Agregado por {{ $expense->creator->name }} • {{ $attendeeCount }} personas
                                            </div>
                                        </div>

                                        <div class="flex flex-col lg:items-end gap-2">
                                            <div class="text-right">
                                                <div class="text-lg lg:text-xl font-bold text-gray-900 dark:text-white">${{ number_format($expense->amount, 2) }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-500">Total</div>
                                            </div>
                                            
                                            <div class="text-right">
                                                <div class="text-lg font-bold text-red-600 dark:text-red-400">${{ number_format($remaining, 2) }}</div>
                                                <div class="text-xs text-red-600 dark:text-red-400">Falta por pagar</div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($userSplit)
                                        <div class="mt-3 bg-white dark:bg-gray-800 rounded-lg p-3 border border-red-200 dark:border-red-800">
                                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                                                <div>
                                                    <div class="font-medium text-gray-700 dark:text-gray-300">Tu Parte</div>
                                                    <div class="text-lg font-bold">${{ number_format($userSplit->share_amount, 2) }}</div>
                                                </div>

                                                <div>
                                                    <div class="font-medium text-gray-700 dark:text-gray-300">Pagaste</div>
                                                    <div class="text-lg font-bold text-green-600">${{ number_format($userSplit->paid_amount, 2) }}</div>
                                                </div>

                                                <div>
                                                    <div class="font-medium text-gray-700 dark:text-gray-300">
                                                        {{ $userSplit->getRemainingAmount() > 0 ? 'Te Falta' : 'Pagaste Demás' }}
                                                    </div>
                                                    <div class="text-lg font-bold {{ $userSplit->getRemainingAmount() > 0 ? 'text-red-600' : 'text-yellow-600' }}">
                                                        ${{ number_format($userSplit->getRemainingAmount() > 0 ? $userSplit->getRemainingAmount() : $userSplit->getOverpaidAmount(), 2) }}
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="font-medium text-gray-700 dark:text-gray-300">Tu Estado</div>
                                                    <div class="mt-1">
                                                        @if($userSplit->isPending())
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                                Pendiente
                                                            </span>
                                                        @elseif($userSplit->isPartial())
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                                                Parcial
                                                            </span>
                                                        @elseif($userSplit->isPaid())
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                                Pagado
                                                            </span>
                                                        @elseif($userSplit->isOverpaid())
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                                Sobrepagado
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Show attached images for this expense --}}
                                    @if($expense->images && $expense->images->count() > 0)
                                        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded border">
                                            <h6 class="font-medium text-gray-700 dark:text-gray-300 mb-2">📎 Fotos del Gasto ({{ $expense->images->count() }})</h6>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                                @foreach($expense->images as $image)
                                                    <div class="relative group">
                                                        <img src="{{ $image->url }}"
                                                             alt="{{ $image->original_name }}"
                                                             class="w-full h-16 lg:h-20 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity"
                                                             onclick="window.open('{{ $image->url }}', '_blank')">
                                                        <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 rounded-b opacity-0 group-hover:opacity-100 transition-opacity">
                                                            {{ $image->formatted_size }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Haz clic en las imágenes para ver tamaño completo
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <div class="text-4xl mb-4">🎉</div>
                            <h3 class="text-lg font-semibold mb-2">¡Todos los gastos están pagados!</h3>
                            <p>No hay pagos pendientes para este evento.</p>
                        </div>
                    @endif
                </div>

                {{-- All Expenses Tab --}}
                <div id="expenses-content" class="tab-content hidden">
                    @forelse($event->expenses as $expense)
                        @php
                            $userSplit = $expense->splits->where('user_id', auth()->id())->first();
                            $attendeeCount = $expense->splits->count();
                        @endphp

                        <div class="filterable-expense border border-gray-200 dark:border-gray-700 rounded-lg mb-4 p-3 lg:p-4" 
                             data-creator-id="{{ $expense->creator->id }}" 
                             data-category="{{ $expense->category }}" 
                             data-amount="{{ $expense->amount }}" 
                             data-created-date="{{ $expense->created_at->format('Y-m-d') }}">
                            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start mb-3 gap-3">
                                <div class="flex-1">
                                    <div class="flex flex-col lg:flex-row lg:items-center gap-2">
                                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ $expense->title }}</h4>
                                        <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full w-fit">
                                            {{ ucfirst($expense->category) }}
                                        </span>
                                    </div>

                                    @if($expense->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $expense->description }}</p>
                                    @endif

                                    <div class="text-xs lg:text-sm text-gray-500 dark:text-gray-500 mt-1">
                                        Agregado por {{ $expense->creator->name }} • Dividido entre {{ $attendeeCount }} personas
                                    </div>
                                </div>

                                <div class="text-right lg:text-right text-left">
                                    <div class="text-lg lg:text-xl font-bold text-gray-900 dark:text-white">${{ number_format($expense->amount, 2) }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-500">Total</div>
                                </div>
                            </div>

                            @if($userSplit)
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 text-sm">
                                        <div>
                                            <div class="font-medium text-gray-700 dark:text-gray-300">Tu Parte</div>
                                            <div class="text-lg font-bold">${{ number_format($userSplit->share_amount, 2) }}</div>
                                        </div>

                                        <div>
                                            <div class="font-medium text-gray-700 dark:text-gray-300">Pagaste</div>
                                            <div class="text-lg font-bold text-green-600">${{ number_format($userSplit->paid_amount, 2) }}</div>
                                        </div>

                                        <div>
                                            <div class="font-medium text-gray-700 dark:text-gray-300">
                                                {{ $userSplit->getRemainingAmount() > 0 ? 'Aún Debes' : 'Pagaste Demás' }}
                                            </div>
                                            <div class="text-lg font-bold {{ $userSplit->getRemainingAmount() > 0 ? 'text-red-600' : 'text-yellow-600' }}">
                                                ${{ number_format($userSplit->getRemainingAmount() > 0 ? $userSplit->getRemainingAmount() : $userSplit->getOverpaidAmount(), 2) }}
                                            </div>
                                        </div>

                                        <div>
                                            <div class="font-medium text-gray-700 dark:text-gray-300">Estado</div>
                                            <div class="mt-1">
                                                @if($userSplit->isPending())
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                        Pendiente
                                                    </span>
                                                @elseif($userSplit->isPartial())
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                                        Parcial
                                                    </span>
                                                @elseif($userSplit->isPaid())
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                        Pagado
                                                    </span>
                                                @elseif($userSplit->isOverpaid())
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                        Sobrepagado
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Show payment suggestions --}}
                                    @php
                                        $creditors = $expense->getCreditors();
                                        $debtors = $expense->getDebtors();
                                    @endphp

                                    @if(count($creditors) > 0 && count($debtors) > 0)
                                        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded border">
                                            <h5 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">💡 Sugerencias de Pago</h5>

                                            @foreach($creditors as $creditor)
                                                <div class="text-sm text-blue-800 dark:text-blue-200 mb-1">
                                                    <strong>{{ $creditor['user']->name }}</strong> debe recibir ${{ number_format($creditor['amount'], 2) }}
                                                </div>
                                            @endforeach

                                            @foreach($debtors as $debtor)
                                                @if($debtor['user']->id === auth()->id())
                                                    <div class="text-sm text-blue-800 dark:text-blue-200">
                                                        → Deberías pagar ${{ number_format($debtor['amount'], 2) }} a {{ $creditors[0]['user']->name ?? 'alguien que pagó demás' }}
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Show attached images for this expense --}}
                                    @if($expense->images && $expense->images->count() > 0)
                                        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded border">
                                            <h6 class="font-medium text-gray-700 dark:text-gray-300 mb-2">📎 Fotos Adjuntas ({{ $expense->images->count() }})</h6>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                                @foreach($expense->images as $image)
                                                    <div class="relative group">
                                                        <img src="{{ $image->url }}"
                                                             alt="{{ $image->original_name }}"
                                                             class="w-full h-16 lg:h-20 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity"
                                                             onclick="window.open('{{ $image->url }}', '_blank')">
                                                        <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 rounded-b opacity-0 group-hover:opacity-100 transition-opacity">
                                                            {{ $image->formatted_size }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Haz clic en las imágenes para ver tamaño completo
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <p>Aún no se han agregado gastos. ¡Usa el botón "Agregar Gasto" para comenzar!</p>
                        </div>
                    @endforelse
                </div>

                {{-- Payment Log Tab --}}
                <div id="payments-content" class="tab-content hidden">
                    @if($allPayments->count() > 0)
                        <div class="space-y-4">
                            @foreach($allPayments as $payment)
                                <div class="filterable-payment flex flex-col lg:flex-row lg:items-start lg:justify-between p-3 lg:p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow" 
                                     data-payer-id="{{ $payment->payer->id }}" 
                                     data-creator-id="{{ $payment->expense->creator->id }}" 
                                     data-category="{{ $payment->expense->category }}" 
                                     data-amount="{{ $payment->amount }}" 
                                     data-expense-amount="{{ $payment->expense->amount }}" 
                                     data-paid-date="{{ $payment->paid_at->format('Y-m-d') }}">

                                    {{-- Left section --}}
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center gap-2 text-sm lg:text-base">
                                            <span class="font-semibold text-gray-900 dark:text-white">
                                                {{ $payment->payer->name }}
                                            </span>
                                            <span class="text-gray-500 dark:text-gray-400">pagó</span>
                                            <span class="text-lg font-bold text-green-600 dark:text-green-400">
                                                ${{ number_format($payment->amount, 2) }}
                                            </span>
                                            <span class="text-gray-500 dark:text-gray-400">por</span>
                                            <span class="font-medium text-gray-900 dark:text-white">
                                                {{ $payment->expense->title }}
                                            </span>
                                        </div>

                                        @if($payment->note)
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 italic">
                                                💬 {{ $payment->note }}
                                            </p>
                                        @endif

                                        <div class="mt-2 flex flex-wrap gap-3 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="flex items-center gap-1">📅 {{ $payment->paid_at->format('j M Y, g:i A') }}</span>
                                            <span class="flex items-center gap-1">🏷️ {{ ucfirst($payment->expense->category) }}</span>
                                            <span class="flex items-center gap-1">📝 Agregado por {{ $payment->expense->creator->name }}</span>
                                        </div>
                                    </div>

                                    {{-- Right section --}}
                                    <div class="mt-3 lg:mt-0 lg:ml-4 flex flex-col items-start lg:items-end gap-2">
                                        @if($payment->payer->id === auth()->id())
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                Tu Pago
                                            </span>
                                        @else
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                Pago de Amigo
                                            </span>
                                        @endif

                                        {{-- Payment proof --}}
                                        @if($payment->images && $payment->images->count() > 0)
                                            <div class="text-xs font-medium text-green-700 dark:text-green-300">
                                                🧾 Comprobante ({{ $payment->images->count() }})
                                            </div>
                                            <div class="flex gap-2">
                                                @foreach($payment->images as $image)
                                                    <img src="{{ $image->url }}"
                                                         alt="Comprobante de pago"
                                                         class="w-12 h-9 lg:w-16 lg:h-12 object-cover rounded-lg border border-gray-300 dark:border-gray-600 cursor-pointer hover:scale-105 transition-transform"
                                                         onclick="window.open('{{ $image->url }}', '_blank')">
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                            <p>Aún no se han registrado pagos. ¡Usa <span class="font-semibold">"Registrar Pago"</span> para registrar tus contribuciones!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- JavaScript for tab functionality and filtering --}}
        <script>
            function showTab(tabName) {
                // Hide all tab content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });

                // Remove active styles from all tab buttons (desktop)
                document.querySelectorAll('.tab-button').forEach(button => {
                    button.classList.remove('border-red-500', 'text-red-600', 'dark:text-red-400');
                    button.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300', 'dark:text-gray-400', 'dark:hover:text-gray-300');
                });

                // Show selected tab content
                document.getElementById(tabName + '-content').classList.remove('hidden');

                // Add active styles to selected tab button (desktop)
                const activeButton = document.getElementById(tabName + '-tab');
                if (activeButton) {
                    activeButton.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300', 'dark:text-gray-400', 'dark:hover:text-gray-300');
                    activeButton.classList.add('border-red-500', 'text-red-600', 'dark:text-red-400');
                }

                // Update mobile selector
                const mobileSelector = document.getElementById('mobile-tab-selector');
                if (mobileSelector) {
                    mobileSelector.value = tabName;
                }

                // Re-apply filters after tab change
                applyFilters();
            }

            // Function for mobile dropdown
            function showTabFromSelect(tabName) {
                showTab(tabName);
            }

            // Advanced filtering system
            function applyFilters() {
                const attendeeFilter = document.getElementById('filter-attendee').value;
                const categoryFilter = document.getElementById('filter-category').value;
                const amountFilter = document.getElementById('filter-amount').value;
                const dateFilter = document.getElementById('filter-date').value;

                let visibleExpenses = 0;
                let visiblePayments = 0;
                let activeFilters = 0;

                // Count active filters
                if (attendeeFilter) activeFilters++;
                if (categoryFilter) activeFilters++;
                if (amountFilter) activeFilters++;
                if (dateFilter) activeFilters++;

                // Get current date for date filtering
                const today = new Date();
                const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);

                // Filter expenses (by creator only)
                document.querySelectorAll('.filterable-expense').forEach(expense => {
                    let show = true;

                    // Attendee filter (expense creator only)
                    if (attendeeFilter && expense.dataset.creatorId !== attendeeFilter) {
                        show = false;
                    }

                    // Category filter
                    if (categoryFilter && expense.dataset.category !== categoryFilter) {
                        show = false;
                    }

                    // Amount filter
                    if (amountFilter && !matchesAmountRange(parseFloat(expense.dataset.amount), amountFilter)) {
                        show = false;
                    }

                    // Date filter
                    if (dateFilter && !matchesDateRange(expense.dataset.createdDate, dateFilter, today, weekAgo, monthAgo)) {
                        show = false;
                    }

                    if (show) {
                        expense.style.display = 'block';
                        visibleExpenses++;
                    } else {
                        expense.style.display = 'none';
                    }
                });

                // Filter payments (by payer only)
                document.querySelectorAll('.filterable-payment').forEach(payment => {
                    let show = true;

                    // Attendee filter (payment payer only)
                    if (attendeeFilter && payment.dataset.payerId !== attendeeFilter) {
                        show = false;
                    }

                    // Category filter
                    if (categoryFilter && payment.dataset.category !== categoryFilter) {
                        show = false;
                    }

                    // Amount filter (use payment amount for payments)
                    if (amountFilter && !matchesAmountRange(parseFloat(payment.dataset.amount), amountFilter)) {
                        show = false;
                    }

                    // Date filter (use paid date for payments)
                    if (dateFilter && !matchesDateRange(payment.dataset.paidDate, dateFilter, today, weekAgo, monthAgo)) {
                        show = false;
                    }

                    if (show) {
                        payment.style.display = 'flex';
                        visiblePayments++;
                    } else {
                        payment.style.display = 'none';
                    }
                });

                // Update filter count display
                updateFilterCount(activeFilters, visibleExpenses, visiblePayments);
            }

            function matchesAmountRange(amount, range) {
                switch(range) {
                    case '0-50': return amount >= 0 && amount <= 50;
                    case '50-100': return amount > 50 && amount <= 100;
                    case '100-200': return amount > 100 && amount <= 200;
                    case '200-500': return amount > 200 && amount <= 500;
                    case '500+': return amount > 500;
                    default: return true;
                }
            }

            function matchesDateRange(dateString, range, today, weekAgo, monthAgo) {
                const itemDate = new Date(dateString);
                const todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                
                switch(range) {
                    case 'today': 
                        return itemDate.toDateString() === todayDate.toDateString();
                    case 'week': 
                        return itemDate >= weekAgo;
                    case 'month': 
                        return itemDate >= monthAgo;
                    case 'older': 
                        return itemDate < monthAgo;
                    default: 
                        return true;
                }
            }

            function updateFilterCount(activeFilters, visibleExpenses, visiblePayments) {
                const filterCountElement = document.getElementById('filter-count');
                
                if (activeFilters > 0) {
                    filterCountElement.textContent = `${activeFilters} filtro${activeFilters !== 1 ? 's' : ''} activo${activeFilters !== 1 ? 's' : ''} | ${visibleExpenses} gastos, ${visiblePayments} pagos`;
                    filterCountElement.classList.remove('hidden');
                } else {
                    filterCountElement.classList.add('hidden');
                }
            }

            function clearAllFilters() {
                document.getElementById('filter-attendee').value = '';
                document.getElementById('filter-category').value = '';
                document.getElementById('filter-amount').value = '';
                document.getElementById('filter-date').value = '';
                applyFilters();
            }

            // Set default tab on page load
            document.addEventListener('DOMContentLoaded', function() {
                showTab('pending');
            });
        </script>

    </div>
</x-filament-panels::page>
