<div class="space-y-6">
    {{-- Friend Information Header --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-content p-6">
            <div class="flex items-center space-x-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900">
                    <span class="text-lg font-medium text-primary-600 dark:text-primary-400">
                        {{ substr($friend->friend_name, 0, 1) }}
                    </span>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $friend->friend_name }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $friend->friend_email }}</p>
                </div>
            </div>
            
            {{-- Net Balance Summary --}}
            <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-lg bg-danger-50 p-4 dark:bg-danger-950">
                    <p class="text-sm font-medium text-danger-600 dark:text-danger-400">You Owe Them</p>
                    <p class="text-2xl font-bold text-danger-600 dark:text-danger-400">${{ number_format(max(0, $friend->i_owe_them), 2) }}</p>
                </div>
                <div class="rounded-lg bg-success-50 p-4 dark:bg-success-950">
                    <p class="text-sm font-medium text-success-600 dark:text-success-400">They Owe You</p>
                    <p class="text-2xl font-bold text-success-600 dark:text-success-400">${{ number_format(max(0, $friend->they_owe_me), 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Net Balance</p>
                    <p class="text-2xl font-bold 
                        @if($friend->net_balance > 0) text-success-600 dark:text-success-400
                        @elseif($friend->net_balance < 0) text-danger-600 dark:text-danger-400
                        @else text-gray-600 dark:text-gray-400 @endif">
                        @if($friend->net_balance > 0)
                            +${{ number_format($friend->net_balance, 2) }}
                        @elseif($friend->net_balance < 0)
                            -${{ number_format(abs($friend->net_balance), 2) }}
                        @else
                            Settled
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Expenses You Created --}}
    @if($expensesICreated->count() > 0)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header flex flex-col gap-3 px-6 py-4">
            <div class="flex items-center gap-3">
                <h4 class="fi-section-header-heading text-lg font-semibold leading-6 text-gray-950 dark:text-white">
                    Expenses You Created ({{ $expensesICreated->count() }})
                </h4>
            </div>
            <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                Expenses where {{ $friend->friend_name }} owes you money
            </p>
        </div>
        <div class="fi-section-content divide-y divide-gray-200 dark:divide-white/10">
            @foreach($expensesICreated as $expense)
                @php
                    $participant = $expense->participants->first();
                    $remaining = $participant ? $participant->getRemainingAmount() : 0;
                @endphp
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h5 class="text-sm font-medium text-gray-950 dark:text-white">{{ $expense->title }}</h5>
                            @if($expense->description)
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $expense->description }}</p>
                            @endif
                            <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                <span>Total: ${{ number_format($expense->total_amount, 2) }}</span>
                                <span class="fi-badge fi-badge-color-gray fi-badge-size-xs">{{ ucfirst($expense->category) }}</span>
                                <span>{{ $expense->created_at->format('M j, Y') }}</span>
                            </div>
                        </div>
                        <div class="ml-4 text-right">
                            @if($participant)
                                <p class="text-sm font-medium text-gray-950 dark:text-white">
                                    Assigned: ${{ number_format($participant->assigned_amount, 2) }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Paid: ${{ number_format($participant->paid_amount, 2) }}
                                </p>
                                @if($remaining > 0)
                                    <p class="text-sm font-medium text-success-600 dark:text-success-400">
                                        Owes: ${{ number_format($remaining, 2) }}
                                    </p>
                                @else
                                    <span class="fi-badge fi-badge-color-success fi-badge-size-xs">
                                        Paid
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Expenses They Created --}}
    @if($expensesTheyCreated->count() > 0)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header flex flex-col gap-3 px-6 py-4">
            <div class="flex items-center gap-3">
                <h4 class="fi-section-header-heading text-lg font-semibold leading-6 text-gray-950 dark:text-white">
                    Expenses {{ $friend->friend_name }} Created ({{ $expensesTheyCreated->count() }})
                </h4>
            </div>
            <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                Expenses where you owe {{ $friend->friend_name }} money
            </p>
        </div>
        <div class="fi-section-content divide-y divide-gray-200 dark:divide-white/10">
            @foreach($expensesTheyCreated as $expense)
                @php
                    $participant = $expense->participants->first();
                    $remaining = $participant ? $participant->getRemainingAmount() : 0;
                @endphp
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h5 class="text-sm font-medium text-gray-950 dark:text-white">{{ $expense->title }}</h5>
                            @if($expense->description)
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $expense->description }}</p>
                            @endif
                            <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                <span>Total: ${{ number_format($expense->total_amount, 2) }}</span>
                                <span class="fi-badge fi-badge-color-gray fi-badge-size-xs">{{ ucfirst($expense->category) }}</span>
                                <span>{{ $expense->created_at->format('M j, Y') }}</span>
                            </div>
                        </div>
                        <div class="ml-4 text-right">
                            @if($participant)
                                <p class="text-sm font-medium text-gray-950 dark:text-white">
                                    Your Share: ${{ number_format($participant->assigned_amount, 2) }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    You Paid: ${{ number_format($participant->paid_amount, 2) }}
                                </p>
                                @if($remaining > 0)
                                    <p class="text-sm font-medium text-danger-600 dark:text-danger-400">
                                        You Owe: ${{ number_format($remaining, 2) }}
                                    </p>
                                @else
                                    <span class="fi-badge fi-badge-color-success fi-badge-size-xs">
                                        Paid
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- No Expenses Message --}}
    @if($expensesICreated->count() === 0 && $expensesTheyCreated->count() === 0)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-content p-12 text-center">
            <div class="fi-empty-state mx-auto max-w-md">
                <div class="fi-empty-state-icon-ctn mb-4 flex justify-center">
                    <svg class="fi-empty-state-icon h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-1 1m0 0l-1 1m1-1v4m-1-4l-1-1" />
                    </svg>
                </div>
                <h3 class="fi-empty-state-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    No active expenses
                </h3>
                <p class="fi-empty-state-description mt-1 text-sm text-gray-500 dark:text-gray-400">
                    You and {{ $friend->friend_name }} have no active shared expenses.
                </p>
            </div>
        </div>
    </div>
    @endif
</div>