<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\TransactionService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TransactionForm extends Component
{
    #[Locked]
    public $transactionId = null;

    #[Locked]
    public $transaction_number = '';

    public $type = 'transfer';
    public $from_account_id = '';
    public $to_account_id = '';
    public $amount = '';
    public $description = '';
    public $notes = '';

    protected function rules(): array
    {
        $rules = [
            'type' => 'required|in:transfer,deposit',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'to_account_id' => 'required|exists:accounts,id',
        ];

        if ($this->type === 'transfer') {
            $rules['from_account_id'] = 'required|exists:accounts,id|different:to_account_id';
        }

        return $rules;
    }

    protected $messages = [
        'type.required' => 'El tipo de transacción es obligatorio.',
        'type.in' => 'El tipo debe ser transferencia o ingreso a caja chica.',
        'from_account_id.required' => 'Debe seleccionar una cuenta de origen.',
        'from_account_id.different' => 'La cuenta de origen debe ser diferente a la de destino.',
        'to_account_id.required' => 'Debe seleccionar una cuenta de destino.',
        'amount.required' => 'El monto es obligatorio.',
        'amount.numeric' => 'El monto debe ser un número.',
        'amount.min' => 'El monto debe ser mayor a 0.',
        'description.required' => 'La descripción es obligatoria.',
        'description.max' => 'La descripción no puede tener más de 500 caracteres.',
        'notes.max' => 'Las notas no pueden tener más de 1000 caracteres.',
        'from_account_id.exists' => 'La cuenta de origen no existe.',
        'to_account_id.exists' => 'La cuenta de destino no existe.',
    ];

    public function mount($transactionId = null)
    {
        if (! $transactionId) {
            return;
        }

        $transaction = Transaction::findOrFail($transactionId);
        $this->transactionId = $transaction->id;
        $this->transaction_number = $transaction->transaction_number;
        $this->type = $transaction->type === 'deposit' ? 'deposit' : 'transfer';
        $this->from_account_id = $transaction->from_account_id;
        $this->to_account_id = $transaction->to_account_id;
        $this->amount = $transaction->amount;
        $this->description = $transaction->description;
        $this->notes = $transaction->notes;
    }

    public function updatedType(): void
    {
        if ($this->type === 'deposit') {
            $this->from_account_id = '';
            $this->to_account_id = $this->treasury()?->id ?? '';
        }
    }

    public function save()
    {
        abort_unless(auth()->user()?->hasAnyRole(['boss', 'treasurer']), 403);

        if ($this->type === 'deposit') {
            $this->from_account_id = '';
            $this->to_account_id = $this->treasury()?->id ?? '';
        }

        $this->validate();

        $service = app(TransactionService::class);
        $to = Account::find($this->to_account_id);

        if ($this->type === 'deposit') {
            if ($to?->type !== 'treasury') {
                $this->addError('to_account_id', 'Los ingresos a caja chica solo pueden ir a Tesorería.');

                return;
            }
        } else {
            $violations = $service->transferViolations(Account::find($this->from_account_id), $to, (float) $this->amount);
            if ($violations) {
                foreach ($violations as $field => $message) {
                    $this->addError($field, $message);
                }
                $this->dispatch('cote-toast', type: 'error', message: reset($violations));

                return;
            }
        }

        $fields = [
            'type' => $this->type,
            'from_account_id' => $this->type === 'deposit' ? null : $this->from_account_id,
            'to_account_id' => $this->to_account_id,
            'amount' => $this->amount,
            'description' => $this->description,
            'notes' => $this->notes,
        ];

        try {
            if ($this->transactionId) {
                $transaction = Transaction::findOrFail($this->transactionId);
                if ($transaction->status !== 'pending') {
                    $this->dispatch('cote-toast', type: 'error', message: 'Solo se pueden editar transacciones pendientes.');

                    return;
                }
                $transaction->update($fields);
                session()->flash('message', 'Transacción actualizada correctamente.');
            } else {
                $service->createTransaction([...$fields, 'created_by' => auth()->id(), 'status' => 'pending']);
                session()->flash('message', 'Transacción creada correctamente.');
            }

            return redirect()->route('transactions.index');
        } catch (\Exception $e) {
            $this->dispatch('cote-toast', type: 'error', message: \App\Support\ErrorMessage::for($e, 'Error al procesar la transacción'));
        }
    }

    private function treasury(): ?Account
    {
        return Account::where('type', 'treasury')->first();
    }

    public function render()
    {
        return view('livewire.transaction-form', [
            'treasury' => $this->treasury(),
            'accounts' => Account::with('person')
                ->where('is_enabled', true)
                ->where('type', '!=', 'treasury')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
