<?php

namespace App\Livewire;

use App\Models\Transaction;
use App\Models\Account;
use App\Services\TransactionService;
use Illuminate\Support\Str;
use Livewire\Component;

class TransactionForm extends Component
{
    public $transactionId = null;
    public $transaction_number = '';
    public $type = 'transfer';
    public $from_account_id = '';
    public $to_account_id = '';
    public $amount = '';
    public $description = '';
    public $notes = '';
    public $status = 'pending';

    protected $rules = [
        'type' => 'required|in:transfer,payment,adjustment',
        'from_account_id' => 'required|exists:accounts,id',
        'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
        'amount' => 'required|numeric|min:0.01',
        'description' => 'required|string|max:500',
        'notes' => 'nullable|string',
        'status' => 'required|in:pending,approved,rejected,completed',
    ];

    protected $messages = [
        'type.required' => 'El tipo de transacción es obligatorio.',
        'from_account_id.required' => 'Debe seleccionar una cuenta de origen.',
        'to_account_id.required' => 'Debe seleccionar una cuenta de destino.',
        'to_account_id.different' => 'La cuenta de destino debe ser diferente a la de origen.',
        'amount.required' => 'El monto es obligatorio.',
        'amount.numeric' => 'El monto debe ser un número.',
        'amount.min' => 'El monto debe ser mayor a 0.',
        'description.required' => 'La descripción es obligatoria.',
        'description.max' => 'La descripción no puede tener más de 500 caracteres.',
        'from_account_id.exists' => 'La cuenta de origen no existe.',
        'to_account_id.exists' => 'La cuenta de destino no existe.',
    ];

    public function mount($transactionId = null)
    {
        if ($transactionId) {
            $this->transactionId = $transactionId;
            $transaction = Transaction::findOrFail($transactionId);
            $this->transaction_number = $transaction->transaction_number;
            $this->type = $transaction->type;
            $this->from_account_id = $transaction->from_account_id;
            $this->to_account_id = $transaction->to_account_id;
            $this->amount = $transaction->amount;
            $this->description = $transaction->description;
            $this->notes = $transaction->notes;
            $this->status = $transaction->status;
        } else {
            // Generate transaction number for new transactions
            $this->transaction_number = 'TXN-' . date('Ymd') . '-' . Str::upper(Str::random(6));
        }
    }

    public function save()
    {
        $this->validate();

        try {
            $transactionService = new TransactionService();

            if ($this->transactionId) {
                // Update existing transaction
                $transaction = Transaction::findOrFail($this->transactionId);
                
                if ($transaction->status !== 'pending') {
                    session()->flash('error', 'Solo se pueden editar transacciones pendientes.');
                    return;
                }
                
                $transaction->update([
                    'type' => $this->type,
                    'from_account_id' => $this->from_account_id,
                    'to_account_id' => $this->to_account_id,
                    'amount' => $this->amount,
                    'description' => $this->description,
                    'notes' => $this->notes,
                ]);

                session()->flash('message', 'Transacción actualizada correctamente.');
            } else {
                // Verificar fondos suficientes para transferencias
                if ($this->type === 'transfer' && !$transactionService->hasSufficientFunds($this->from_account_id, $this->amount)) {
                    session()->flash('error', 'La cuenta origen no tiene fondos suficientes.');
                    return;
                }

                // Create new transaction
                $transactionService->createTransaction([
                    'type' => $this->type,
                    'from_account_id' => $this->from_account_id,
                    'to_account_id' => $this->to_account_id,
                    'amount' => $this->amount,
                    'description' => $this->description,
                    'notes' => $this->notes,
                    'created_by' => auth()->id(),
                    'status' => 'pending',
                ]);

                session()->flash('message', 'Transacción creada correctamente.');
            }

            return redirect()->route('transactions.index');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al procesar la transacción: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $accounts = Account::where('is_enabled', true)->orderBy('name')->get();

        return view('livewire.transaction-form', compact('accounts'));
    }
}
