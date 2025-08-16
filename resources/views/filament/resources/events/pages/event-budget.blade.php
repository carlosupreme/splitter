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

        {{-- Expenses List --}}
        <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 lg:p-6">
                <h3 class="text-lg lg:text-xl font-semibold text-gray-900 dark:text-white mb-4">🧾 Gastos y Tu Parte</h3>

                @forelse($event->expenses as $expense)
                    @php
                        $userSplit = $expense->splits->where('user_id', auth()->id())->first();
                        $attendeeCount = $expense->splits->count();
                    @endphp

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg mb-4 p-3 lg:p-4">
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

                                {{-- Show who has overpaid for this expense --}}
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
        </div>

        {{-- Payment Log --}}
        @php
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
            <div class="p-4 lg:p-6">
                <h3 class="text-lg lg:text-xl font-bold text-gray-900 dark:text-white mb-5 flex items-center gap-2">
                    💳 Registro de Pagos
                </h3>

                @if($allPayments->count() > 0)
                    <div class="space-y-4">
                        @foreach($allPayments as $payment)
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between p-3 lg:p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow">

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
</x-filament-panels::page>
