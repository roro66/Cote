<?php

namespace App\Livewire;

use App\Models\Transaction;
use App\Services\TransactionService;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionApproval extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'pending';
    public $showingDetails = false;
    public $selectedTransaction = null;
    public $rejectionReason = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showDetails($transactionId)
    {
        $this->selectedTransaction = Transaction::with(['fromAccount', 'toAccount', 'creator'])
            ->findOrFail($transactionId);
        $this->showingDetails = true;
    }

    public function hideDetails()
    {
        $this->showingDetails = false;
        $this->selectedTransaction = null;
        $this->rejectionReason = '';
    }

    public function approve($transactionId)
    {
        try {
            $transaction = Transaction::findOrFail($transactionId);
            $transactionService = new TransactionService();
            
            // Verificar fondos suficientes antes de aprobar
            if ($transaction->type === 'transfer') {
                if (!$transactionService->hasSufficientFunds($transaction->from_account_id, $transaction->amount)) {
                    session()->flash('error', 'La cuenta origen no tiene fondos suficientes para esta transacción.');
                    return;
                }
            }
            
            $transactionService->approveTransaction($transaction, auth()->id());
            
            session()->flash('message', "Transacción {$transaction->transaction_number} aprobada correctamente.");
            $this->hideDetails();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al aprobar transacción: ' . $e->getMessage());
        }
    }

    public function reject($transactionId)
    {
        $this->validate([
            'rejectionReason' => 'required|string|min:10|max:500'
        ], [
            'rejectionReason.required' => 'Debe proporcionar un motivo de rechazo.',
            'rejectionReason.min' => 'El motivo debe tener al menos 10 caracteres.',
            'rejectionReason.max' => 'El motivo no puede exceder 500 caracteres.',
        ]);

        try {
            $transaction = Transaction::findOrFail($transactionId);
            $transactionService = new TransactionService();
            
            $transactionService->rejectTransaction($transaction, auth()->id(), $this->rejectionReason);
            
            session()->flash('message', "Transacción {$transaction->transaction_number} rechazada.");
            $this->hideDetails();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al rechazar transacción: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $transactions = Transaction::with(['fromAccount', 'toAccount', 'creator'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('transaction_number', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhereHas('fromAccount', function ($subQuery) {
                          $subQuery->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('toAccount', function ($subQuery) {
                          $subQuery->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.transaction-approval', compact('transactions'));
    }
}
