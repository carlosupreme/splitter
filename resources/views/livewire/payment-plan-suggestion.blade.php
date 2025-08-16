<div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
    <div class="p-4 lg:p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg lg:text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                💳 Plan de Transferencias Óptimo
            </h3>
            <button wire:click="calculateOptimalTransfers"
                    class="px-3 py-1 text-sm bg-blue-100 hover:bg-blue-200 dark:bg-blue-800 dark:hover:bg-blue-700 text-blue-800 dark:text-blue-100 rounded-lg transition-colors">
                🔄 Actualizar
            </button>
        </div>

        @if (count($optimalTransfers) === 0)
            <div class="text-center py-8">
                <div class="text-4xl mb-3">🎉</div>
                <h4 class="text-lg font-semibold text-green-600 dark:text-green-400 mb-2">
                    ¡Todas las cuentas están saldadas!
                </h4>
                <p class="text-gray-600 dark:text-gray-400">
                    No se necesitan transferencias adicionales.
                </p>
            </div>
        @else
            <div class="space-y-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
                    <div class="flex items-start gap-3">
                        <div class="text-blue-600 dark:text-blue-400 text-lg">💡</div>
                        <div>
                            <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-1">
                                Sugerencia de Transferencias
                            </h4>
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                Para saldar todas las deudas, se necesitan <strong>{{ count($optimalTransfers) }}
                                    transferencia{{ count($optimalTransfers) === 1 ? '' : 's' }}</strong>.
                                Esto es la forma más eficiente de equilibrar las cuentas entre todos.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Transfer List --}}
                <div class="space-y-3">
                    @foreach ($optimalTransfers as $index => $transfer)
                        <div
                            class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="flex items-center justify-center w-6 h-6 bg-blue-600 text-white text-sm font-bold rounded-full">
                                            {{ $index + 1 }}
                                        </span>
                                        <div class="flex items-center gap-2 text-sm lg:text-base">
                                            <span class="font-semibold text-gray-900 dark:text-white">
                                                {{ $transfer['from_user']['name'] }}
                                                @if($transfer['from_user']['id'] === auth()->id())
                                                    <span class="text-blue-600 dark:text-blue-400">(Tú)</span>
                                                @endif
                                            </span>
                                            <span class="text-gray-500 dark:text-gray-400">transfiere</span>
                                            <span class="text-lg font-bold text-green-600 dark:text-green-400">
                                                ${{ number_format($transfer['amount'], 2) }}
                                            </span>
                                            <span class="text-gray-500 dark:text-gray-400">a</span>
                                            <span class="font-semibold text-gray-900 dark:text-white">
                                                {{ $transfer['to_user']['name'] }}
                                                @if($transfer['to_user']['id'] === auth()->id())
                                                    <span class="text-blue-600 dark:text-blue-400">(Tú)</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>

                                    <div class="text-xs text-gray-500 dark:text-gray-400 lg:ml-9">
                                        {{ $transfer['from_user']['name'] }} debe
                                        ${{ number_format($attendeeBalances[$transfer['from_user']['id']]['net_balance'] * -1, 2) }}
                                        •
                                        {{ $transfer['to_user']['name'] }} le deben
                                        ${{ number_format($attendeeBalances[$transfer['to_user']['id']]['net_balance'], 2) }}
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    @php
                                        $existingTransfer = App\Models\PendingTransfer::where([
                                                               'event_id' => $this->event->id,
                                                               'from_user_id' => $transfer['from_user']['id'],
                                                               'to_user_id' => $transfer['to_user']['id'],
                                                               'status' => 'pending'
                                                            ])->first();
                                    @endphp
                                    @if($existingTransfer)
                                        <span
                                            class="px-3 py-2bg-yellow-100 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-100 text-sm rounded-lg">
                                            💰 Solicitud enviada @if($transfer['to_user']['id'] === auth()->id())
                                                (confirma abajo)
                                            @endif
                                        </span>
                                    @elseif($transfer['from_user']['id'] === auth()->id())
                                        <span
                                            class="px-3 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white dark:text-gray-100 text-sm font-medium rounded-lg transition-colors flex items-center gap-1">
                                            📨 Debes transferir
                                        </span>
                                    @elseif($transfer['to_user']['id'] === auth()->id())
                                        <span
                                            class="px-3 py-2bg-yellow-100 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-100 text-sm rounded-lg">
                                            💰 Debes recibir dinero
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm rounded-lg">
                                            👥 Entre otros usuarios
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Summary Section --}}
                <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
                    @php
                        $totalTransferAmount = collect($optimalTransfers)->sum('amount');
                        $userInvolvedTransfers = collect($optimalTransfers)->filter(function($transfer) {
                            return $transfer['from_user']['id'] === auth()->id() || $transfer['to_user']['id'] === auth()->id();
                        });
                        $userWillPay = $userInvolvedTransfers->where('from_user.id', auth()->id())->sum('amount');
                        $userWillReceive = $userInvolvedTransfers->where('to_user.id', auth()->id())->sum('amount');
                    @endphp

                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg text-center">
                        <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Total a Transferir</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-white">
                            ${{ number_format($totalTransferAmount, 2) }}</div>
                    </div>

                    @if($userWillPay > 0)
                        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg text-center">
                            <div class="text-sm font-medium text-red-600 dark:text-red-400">Tú Transferirás</div>
                            <div class="text-xl font-bold text-red-900 dark:text-red-100">
                                ${{ number_format($userWillPay, 2) }}</div>
                        </div>
                    @endif

                    @if($userWillReceive > 0)
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg text-center">
                            <div class="text-sm font-medium text-green-600 dark:text-green-400">Tú Recibirás</div>
                            <div class="text-xl font-bold text-green-900 dark:text-green-100">
                                ${{ number_format($userWillReceive, 2) }}</div>
                        </div>
                    @endif
                </div>

{{--                --}}{{-- Pending Transfer Requests --}}
{{--                @if(count($pendingTransfers) > 0)--}}
{{--                    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">--}}
{{--                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">--}}
{{--                            ⏳ Transferencias Pendientes de Confirmación--}}
{{--                        </h4>--}}

{{--                        <div class="space-y-3">--}}
{{--                            @foreach($pendingTransfers as $transfer)--}}
{{--                                <div--}}
{{--                                    class="border border-orange-200 dark:border-orange-700 rounded-lg p-4 bg-orange-50 dark:bg-orange-900/20">--}}
{{--                                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">--}}
{{--                                        <div class="flex-1">--}}
{{--                                            <div class="flex items-center gap-2 mb-2">--}}
{{--                                                <span--}}
{{--                                                    class="px-2 py-1 bg-orange-100 dark:bg-orange-800 text-orange-800 dark:text-orange-100 text-xs font-medium rounded-full">--}}
{{--                                                    Pendiente--}}
{{--                                                </span>--}}
{{--                                                <span class="text-sm text-gray-500 dark:text-gray-400">--}}
{{--                                                    {{ $transfer['requested_at']->diffForHumans() }}--}}
{{--                                                </span>--}}
{{--                                            </div>--}}

{{--                                            <div class="flex items-center gap-2 text-sm lg:text-base">--}}
{{--                                                <span class="font-semibold text-gray-900 dark:text-white">--}}
{{--                                                    {{ $transfer['from_user']['name'] }}--}}
{{--                                                    @if($transfer['is_mine'])--}}
{{--                                                        <span class="text-blue-600 dark:text-blue-400">(Tú)</span>--}}
{{--                                                    @endif--}}
{{--                                                </span>--}}
{{--                                                <span class="text-gray-500 dark:text-gray-400">quiere transferir</span>--}}
{{--                                                <span class="text-lg font-bold text-orange-600 dark:text-orange-400">--}}
{{--                                                    ${{ number_format($transfer['amount'], 2) }}--}}
{{--                                                </span>--}}
{{--                                                <span class="text-gray-500 dark:text-gray-400">a</span>--}}
{{--                                                <span class="font-semibold text-gray-900 dark:text-white">--}}
{{--                                                    {{ $transfer['to_user']['name'] }}--}}
{{--                                                    @if($transfer['can_confirm'])--}}
{{--                                                        <span class="text-blue-600 dark:text-blue-400">(Tú)</span>--}}
{{--                                                    @endif--}}
{{--                                                </span>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}

{{--                                        <div class="flex gap-2">--}}
{{--                                            @if($transfer['can_confirm'])--}}
{{--                                                <button wire:click="showConfirmation({{ $transfer['id'] }})"--}}
{{--                                                        class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-1">--}}
{{--                                                    ✅ Confirmar Recibido--}}
{{--                                                </button>--}}
{{--                                                <button wire:click="rejectTransfer({{ $transfer['id'] }})"--}}
{{--                                                        class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-1">--}}
{{--                                                    ❌ Rechazar--}}
{{--                                                </button>--}}
{{--                                            @elseif($transfer['is_mine'])--}}
{{--                                                <span--}}
{{--                                                    class="px-3 py-2 bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-100 text-sm rounded-lg">--}}
{{--                                                    📤 Tu solicitud enviada--}}
{{--                                                </span>--}}
{{--                                            @else--}}
{{--                                                <span--}}
{{--                                                    class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm rounded-lg">--}}
{{--                                                    👀 Esperando confirmación--}}
{{--                                                </span>--}}
{{--                                            @endif--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            @endforeach--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                @endif--}}
            </div>
        @endif
    </div>

{{--    --}}{{-- Transfer Confirmation Modal --}}
{{--    @if($showTransferModal && !empty($selectedTransfer))--}}
{{--        <div--}}
{{--            x-data="{ show: @entangle('showTransferModal') }"--}}
{{--            x-cloak--}}
{{--            x-show="show"--}}
{{--            x-transition.opacity--}}
{{--            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"--}}
{{--            role="dialog"--}}
{{--            aria-modal="true"--}}
{{--            @keydown.escape.window="show = false"--}}
{{--        >--}}
{{--            --}}{{-- Background overlay --}}
{{--            <div--}}
{{--                x-show="show"--}}
{{--                x-transition.opacity--}}
{{--                class="absolute inset-0 bg-gray-900/60 dark:bg-black/60 z-40"--}}
{{--            ></div>--}}

{{--            --}}{{-- Modal panel --}}
{{--            <div--}}
{{--                x-show="show"--}}
{{--                x-transition:enter="ease-out duration-200"--}}
{{--                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"--}}
{{--                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"--}}
{{--                x-transition:leave="ease-in duration-150"--}}
{{--                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"--}}
{{--                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"--}}
{{--                class="relative z-50 w-full max-w-lg overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-xl ring-1 ring-black/5 dark:ring-white/10"--}}
{{--            >--}}
{{--                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">--}}
{{--                    <div class="sm:flex sm:items-start">--}}
{{--                        <div--}}
{{--                            class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 sm:mx-0 sm:h-10 sm:w-10">--}}
{{--                            💳--}}
{{--                        </div>--}}
{{--                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">--}}
{{--                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">--}}
{{--                                Solicitar Transferencia--}}
{{--                            </h3>--}}

{{--                            <div class="mt-2">--}}
{{--                                <p class="text-sm text-gray-600 dark:text-gray-300">--}}
{{--                                    ¿Confirmas que quieres solicitar esta transferencia? El receptor deberá confirmar--}}
{{--                                    que la recibió.--}}
{{--                                </p>--}}

{{--                                <div class="mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">--}}
{{--                                    <div class="text-center">--}}
{{--                                        <div class="text-lg font-semibold text-gray-900 dark:text-white">--}}
{{--                                            {{ $selectedTransfer['from_user']['name'] ?? '' }}--}}
{{--                                            @if(($selectedTransfer['from_user']['id'] ?? 0) === auth()->id())--}}
{{--                                                <span class="text-blue-600 dark:text-blue-400">(Tú)</span>--}}
{{--                                            @endif--}}
{{--                                        </div>--}}
{{--                                        <div class="my-2 text-2xl">⬇️</div>--}}
{{--                                        <div class="mb-2 text-2xl font-bold text-green-600 dark:text-green-400">--}}
{{--                                            ${{ number_format($selectedTransfer['amount'] ?? 0, 2) }}--}}
{{--                                        </div>--}}
{{--                                        <div class="mb-2 text-2xl">⬇️</div>--}}
{{--                                        <div class="text-lg font-semibold text-gray-900 dark:text-white">--}}
{{--                                            {{ $selectedTransfer['to_user']['name'] ?? '' }}--}}
{{--                                            @if(($selectedTransfer['to_user']['id'] ?? 0) === auth()->id())--}}
{{--                                                <span class="text-blue-600 dark:text-blue-400">(Tú)</span>--}}
{{--                                            @endif--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}

{{--                                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">--}}
{{--                                    Esto enviará una solicitud a {{ $selectedTransfer['to_user']['name'] ?? '' }} para--}}
{{--                                    confirmar que recibió el dinero.--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

{{--                --}}{{-- Actions --}}
{{--                <div class="bg-gray-50 px-4 py-3 dark:bg-gray-700 sm:flex sm:flex-row-reverse sm:px-6">--}}
{{--                    <button--}}
{{--                        wire:click="confirmTransferRequest"--}}
{{--                        class="inline-flex w-full justify-center rounded-md bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"--}}
{{--                    >--}}
{{--                        📨 Enviar Solicitud--}}
{{--                    </button>--}}
{{--                    <button--}}
{{--                        wire:click="cancelTransfer"--}}
{{--                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"--}}
{{--                    >--}}
{{--                        ❌ Cancelar--}}
{{--                    </button>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    @endif--}}

{{--    --}}{{-- Creditor Confirmation Modal --}}
{{--    @if($showConfirmModal && !empty($selectedPendingTransfer))--}}
{{--        <div--}}
{{--            x-data="{ show: @entangle('showConfirmModal') }"--}}
{{--            x-cloak--}}
{{--            x-show="show"--}}
{{--            x-transition.opacity--}}
{{--            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"--}}
{{--            role="dialog"--}}
{{--            aria-modal="true"--}}
{{--            @keydown.escape.window="show = false"--}}
{{--        >--}}
{{--            --}}{{-- Overlay --}}
{{--            <div--}}
{{--                x-show="show"--}}
{{--                x-transition.opacity--}}
{{--                class="absolute inset-0 bg-gray-900/60 dark:bg-black/60 z-40"--}}
{{--            ></div>--}}

{{--            --}}{{-- Panel --}}
{{--            <div--}}
{{--                x-show="show"--}}
{{--                x-transition:enter="ease-out duration-200"--}}
{{--                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"--}}
{{--                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"--}}
{{--                x-transition:leave="ease-in duration-150"--}}
{{--                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"--}}
{{--                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"--}}
{{--                class="relative z-50 w-full max-w-lg overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-xl ring-1 ring-black/5 dark:ring-white/10"--}}
{{--            >--}}
{{--                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">--}}
{{--                    <div class="sm:flex sm:items-start">--}}
{{--                        <div--}}
{{--                            class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 sm:mx-0 sm:h-10 sm:w-10">--}}
{{--                            ✅--}}
{{--                        </div>--}}
{{--                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">--}}
{{--                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">--}}
{{--                                Confirmar Transferencia Recibida--}}
{{--                            </h3>--}}

{{--                            <div class="mt-2">--}}
{{--                                <p class="text-sm text-gray-600 dark:text-gray-300">--}}
{{--                                    ¿Confirmas que recibiste esta transferencia de dinero?--}}
{{--                                </p>--}}

{{--                                <div class="mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">--}}
{{--                                    <div class="text-center">--}}
{{--                                        <div class="text-lg font-semibold text-gray-900 dark:text-white">--}}
{{--                                            {{ $selectedPendingTransfer['from_user']['name'] ?? '' }}--}}
{{--                                        </div>--}}
{{--                                        <div class="my-2 text-2xl">⬇️</div>--}}
{{--                                        <div class="mb-2 text-2xl font-bold text-green-600 dark:text-green-400">--}}
{{--                                            ${{ number_format($selectedPendingTransfer['amount'] ?? 0, 2) }}--}}
{{--                                        </div>--}}
{{--                                        <div class="mb-2 text-2xl">⬇️</div>--}}
{{--                                        <div class="text-lg font-semibold text-gray-900 dark:text-white">--}}
{{--                                            {{ $selectedPendingTransfer['to_user']['name'] ?? '' }} (Tú)--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}

{{--                                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">--}}
{{--                                    Esto registrará el pago en el sistema y actualizará los balances. Solo confirma si--}}
{{--                                    realmente recibiste el dinero.--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

{{--                --}}{{-- Actions --}}
{{--                <div class="bg-gray-50 px-4 py-3 dark:bg-gray-700 sm:flex sm:flex-row-reverse sm:px-6">--}}
{{--                    <button--}}
{{--                        wire:click="confirmReceived"--}}
{{--                        class="inline-flex w-full justify-center rounded-md bg-green-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"--}}
{{--                    >--}}
{{--                        ✅ Sí, Recibí el Dinero--}}
{{--                    </button>--}}
{{--                    <button--}}
{{--                        wire:click="cancelConfirmation"--}}
{{--                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"--}}
{{--                    >--}}
{{--                        ❌ Cancelar--}}
{{--                    </button>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    @endif--}}

</div>

@script
{{--<script>--}}
{{--    // Auto-refresh when transfers are recorded--}}
{{--    $wire.on('transfer-recorded', () => {--}}
{{--        // You can add any additional JavaScript logic here if needed--}}
{{--        console.log('Transfer recorded successfully');--}}
{{--    });--}}
{{--</script>--}}
@endscript
