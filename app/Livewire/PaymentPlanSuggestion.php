<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\BudgetPayment;
use App\Models\BudgetItem;
use App\Models\PendingTransfer;
use Livewire\Component;
use Filament\Notifications\Notification;

class PaymentPlanSuggestion extends Component
{
    public Event $event;
    public array $attendeeBalances = [];
    public array $optimalTransfers = [];
    public array $pendingTransfers = [];
    public bool $showTransferModal = false;
    public bool $showConfirmModal = false;
    public array $selectedTransfer = [];
    public array $selectedPendingTransfer = [];

    public function mount(Event $event)
    {
        $this->event = $event;
        $this->calculateOptimalTransfers();
        $this->loadPendingTransfers();
    }

    public function calculateOptimalTransfers()
    {
        // Get all attendees (organizer + accepted invitees)
        $attendees = collect([$this->event->organizer])->merge($this->event->acceptedInvitees);

        // Calculate net balance for each attendee
        $creditors = collect();
        $debtors = collect();

        foreach ($attendees as $attendee) {
            $summary = $this->event->getBudgetSummaryForUser($attendee->id);
            $netBalance = $summary['total_paid'] - $summary['total_owed'];

            $this->attendeeBalances[$attendee->id] = [
                'user' => $attendee,
                'net_balance' => $netBalance,
                'total_owed' => $summary['total_owed'],
                'total_paid' => $summary['total_paid'],
                'status' => $netBalance > 0 ? 'creditor' : ($netBalance < 0 ? 'debtor' : 'balanced')
            ];

            if ($netBalance > 0.01) { // Small threshold to avoid floating point issues
                $creditors->push([
                    'user' => $attendee,
                    'amount' => $netBalance
                ]);
            } elseif ($netBalance < -0.01) {
                $debtors->push([
                    'user' => $attendee,
                    'amount' => abs($netBalance)
                ]);
            }
        }

        // Calculate optimal transfers using a greedy algorithm
        $this->optimalTransfers = $this->calculateMinimalTransfers($creditors, $debtors);
    }

    public function loadPendingTransfers()
    {
        $this->pendingTransfers = $this->event->load(['pendingTransfers.fromUser', 'pendingTransfers.toUser'])
            ->pendingTransfers
            ->where('status', 'pending')
            ->map(function ($transfer) {
                return [
                    'id' => $transfer->id,
                    'from_user' => $transfer->fromUser,
                    'to_user' => $transfer->toUser,
                    'amount' => $transfer->amount,
                    'requested_at' => $transfer->requested_at,
                    'note' => $transfer->note,
                    'can_confirm' => $transfer->canBeConfirmedBy(auth()->id()),
                    'is_mine' => $transfer->from_user_id === auth()->id(),
                ];
            })
            ->toArray();
    }

    /**
     * Calculate minimal number of transfers to settle all debts
     * Uses a greedy algorithm to minimize transactions
     */
    private function calculateMinimalTransfers($creditors, $debtors)
    {
        $transfers = [];
        $creditorsArray = $creditors->toArray();
        $debtorsArray = $debtors->toArray();

        // Sort creditors and debtors by amount (descending)
        usort($creditorsArray, fn($a, $b) => $b['amount'] <=> $a['amount']);
        usort($debtorsArray, fn($a, $b) => $b['amount'] <=> $a['amount']);

        $i = 0; // creditor index
        $j = 0; // debtor index

        while ($i < count($creditorsArray) && $j < count($debtorsArray)) {
            $creditor = &$creditorsArray[$i];
            $debtor = &$debtorsArray[$j];

            // Calculate transfer amount (minimum of what creditor is owed and what debtor owes)
            $transferAmount = min($creditor['amount'], $debtor['amount']);

            if ($transferAmount > 0.01) { // Only create transfer if meaningful amount
                $transfers[] = [
                    'from_user' => $debtor['user'],
                    'to_user' => $creditor['user'],
                    'amount' => $transferAmount,
                    'id' => uniqid() // Unique ID for tracking
                ];

                // Update remaining amounts
                $creditor['amount'] -= $transferAmount;
                $debtor['amount'] -= $transferAmount;
            }

            // Move to next creditor or debtor based on who is settled
            if ($creditor['amount'] <= 0.01) {
                $i++;
            }
            if ($debtor['amount'] <= 0.01) {
                $j++;
            }
        }

        return $transfers;
    }

    public function requestTransfer($transferId)
    {
        $transfer = collect($this->optimalTransfers)->firstWhere('id', $transferId);
        
        if (!$transfer) {
            Notification::make()
                ->title('Error')
                ->body('Transferencia no encontrada')
                ->danger()
                ->send();
            return;
        }

        // Check if user is the debtor (from_user)
        if ($transfer['from_user']['id'] !== auth()->id()) {
            Notification::make()
                ->title('Error')
                ->body('Solo el deudor puede solicitar una transferencia')
                ->danger()
                ->send();
            return;
        }

        $this->selectedTransfer = $transfer;
        $this->showTransferModal = true;
    }

    public function confirmTransferRequest()
    {
        try {
            // Check if transfer already exists
            $existingTransfer = PendingTransfer::where([
                'event_id' => $this->event->id,
                'from_user_id' => $this->selectedTransfer['from_user']['id'],
                'to_user_id' => $this->selectedTransfer['to_user']['id'],
                'status' => 'pending'
            ])->first();

            if ($existingTransfer) {
                Notification::make()
                    ->title('Transferencia Ya Solicitada')
                    ->body('Ya existe una solicitud de transferencia pendiente para estos usuarios')
                    ->warning()
                    ->send();
                $this->showTransferModal = false;
                return;
            }

            // Create pending transfer request
            PendingTransfer::create([
                'event_id' => $this->event->id,
                'from_user_id' => $this->selectedTransfer['from_user']['id'],
                'to_user_id' => $this->selectedTransfer['to_user']['id'],
                'amount' => $this->selectedTransfer['amount'],
                'status' => 'pending',
                'note' => "Transferencia solicitada para saldar deudas del evento",
                'requested_at' => now(),
            ]);

            $transferAmount = $this->selectedTransfer['amount'];
            $toUserName = $this->selectedTransfer['to_user']['name'];

            // Reload data
            $this->loadPendingTransfers();
            $this->showTransferModal = false;
            $this->selectedTransfer = [];

            Notification::make()
                ->title('¡Solicitud de Transferencia Enviada!')
                ->body("Se envió la solicitud de transferencia de \$" . number_format($transferAmount, 2) . " a {$toUserName}. Esperando confirmación.")
                ->success()
                ->send();

            // Emit event to refresh parent component
            $this->dispatch('transfer-requested');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al Solicitar Transferencia')
                ->body('Ocurrió un error al procesar la solicitud: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function showConfirmation($transferId)
    {
        $transfer = collect($this->pendingTransfers)->firstWhere('id', $transferId);
        
        if (!$transfer || !$transfer['can_confirm']) {
            Notification::make()
                ->title('Error')
                ->body('No tienes permisos para confirmar esta transferencia')
                ->danger()
                ->send();
            return;
        }

        $this->selectedPendingTransfer = $transfer;
        $this->showConfirmModal = true;
    }

    public function confirmReceived()
    {
        try {
            $pendingTransfer = PendingTransfer::find($this->selectedPendingTransfer['id']);
            
            if (!$pendingTransfer || !$pendingTransfer->canBeConfirmedBy(auth()->id())) {
                Notification::make()
                    ->title('Error')
                    ->body('No tienes permisos para confirmar esta transferencia')
                    ->danger()
                    ->send();
                return;
            }

            // Create a "transfer" expense between users
            $transferExpense = BudgetItem::create([
                'event_id' => $this->event->id,
                'created_by' => auth()->id(),
                'title' => "Transferencia: {$pendingTransfer->fromUser->name} → {$pendingTransfer->toUser->name}",
                'description' => "Transferencia confirmada para saldar deudas del evento",
                'type' => 'expense',
                'amount' => $pendingTransfer->amount,
                'category' => 'transfer',
                'split_equally' => false,
            ]);

            // Create splits: 
            // - From user owes the full amount
            // - To user has already "paid" the full amount (gets credit)
            $transferExpense->splits()->create([
                'user_id' => $pendingTransfer->from_user_id,
                'share_amount' => $pendingTransfer->amount,
                'paid_amount' => 0,
                'status' => 'pending'
            ]);

            $transferExpense->splits()->create([
                'user_id' => $pendingTransfer->to_user_id,
                'share_amount' => 0, // They owe nothing
                'paid_amount' => $pendingTransfer->amount, // They already "paid" (received credit)
                'status' => 'paid'
            ]);

            // Record the payment from the "from_user"
            BudgetPayment::create([
                'budget_item_id' => $transferExpense->id,
                'paid_by' => $pendingTransfer->from_user_id,
                'amount' => $pendingTransfer->amount,
                'note' => "Transferencia confirmada - recibida por {$pendingTransfer->toUser->name}",
                'paid_at' => now(),
            ]);

            // Mark transfer as confirmed
            $pendingTransfer->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
            ]);

            $transferAmount = $pendingTransfer->amount;
            $fromUserName = $pendingTransfer->fromUser->name;

            // Reload data
            $this->calculateOptimalTransfers();
            $this->loadPendingTransfers();
            $this->showConfirmModal = false;
            $this->selectedPendingTransfer = [];

            Notification::make()
                ->title('¡Transferencia Confirmada!')
                ->body("Confirmaste que recibiste \$" . number_format($transferAmount, 2) . " de {$fromUserName}")
                ->success()
                ->send();

            // Emit event to refresh parent component
            $this->dispatch('transfer-confirmed');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al Confirmar Transferencia')
                ->body('Ocurrió un error al confirmar la transferencia: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function rejectTransfer($transferId)
    {
        try {
            $pendingTransfer = PendingTransfer::find($transferId);
            
            if (!$pendingTransfer || !$pendingTransfer->canBeConfirmedBy(auth()->id())) {
                Notification::make()
                    ->title('Error')
                    ->body('No tienes permisos para rechazar esta transferencia')
                    ->danger()
                    ->send();
                return;
            }

            $pendingTransfer->update(['status' => 'rejected']);
            $this->loadPendingTransfers();

            Notification::make()
                ->title('Transferencia Rechazada')
                ->body('La solicitud de transferencia fue rechazada')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Ocurrió un error al rechazar la transferencia')
                ->danger()
                ->send();
        }
    }

    public function cancelTransfer()
    {
        $this->showTransferModal = false;
        $this->selectedTransfer = [];
    }

    public function cancelConfirmation()
    {
        $this->showConfirmModal = false;
        $this->selectedPendingTransfer = [];
    }

    public function render()
    {
        return view('livewire.payment-plan-suggestion');
    }
}