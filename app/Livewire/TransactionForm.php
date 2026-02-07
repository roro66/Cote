<?php

namespace App\Livewire;

use App\Models\Transaction;
use App\Models\Account;
use App\Services\TransactionService;
use Illuminate\Support\Str;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

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
    public $adjustment_direction = 'credit'; // 'credit' agrega saldo al destino; 'debit' descuenta del origen
    
    /**
     * ¿Es jefe (boss) el usuario? Considera también el correo admin@cote.com
     */
    protected function isBossUser(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        $byRole = method_exists($user, 'isBoss') ? $user->isBoss() : $user->hasRole('boss');
        $byEmail = strtolower((string) $user->email) === 'admin@cote.com';
        return $byRole || $byEmail;
    }

    /**
     * ¿Puede ver/usar "Ajuste"? (boss o tesorero, o admin por email)
     */
    protected function canAdjustUser(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if ($this->isBossUser()) return true;
        return $user->hasRole('treasurer');
    }
    
    protected function rules(): array
    {
        $base = [
            'type' => 'required|in:transfer,adjustment',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,approved,rejected,completed',
        ];

        if ($this->type === 'adjustment') {
            $base['adjustment_direction'] = 'required|in:credit,debit';
            if ($this->adjustment_direction === 'credit') {
                $base['to_account_id'] = 'required|exists:accounts,id';
                $base['from_account_id'] = 'nullable';
            } else { // debit
                $base['from_account_id'] = 'required|exists:accounts,id';
                $base['to_account_id'] = 'nullable';
            }
        } else { // transfer
            $base['from_account_id'] = 'required|exists:accounts,id';
            $base['to_account_id'] = 'required|exists:accounts,id|different:from_account_id';
        }

        return $base;
    }

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
            $this->type = 'transfer';
            $this->from_account_id = $transaction->from_account_id;
            $this->to_account_id = $transaction->to_account_id;
            $this->amount = $transaction->amount;
            $this->description = $transaction->description;
            $this->notes = $transaction->notes;
            $this->status = $transaction->status;
        } else {
            // Generate transaction number for new transactions
            $this->transaction_number = 'TXN-' . date('Ymd') . '-' . Str::upper(Str::random(6));
            $this->type = 'transfer';
        }
    }

    public function save()
    {
        $this->validate();

        try {
            $transactionService = new TransactionService();

            // Restringir Tesorería solo para jefe (boss)
            $isBoss = $this->isBossUser();
            $canAdjust = $this->canAdjustUser();

            $from = $this->from_account_id ? Account::find($this->from_account_id) : null;
            $to = $this->to_account_id ? Account::find($this->to_account_id) : null;
            $usesTreasury = ($from && $from->type === 'treasury') || ($to && $to->type === 'treasury');
            if ($usesTreasury && !$isBoss) {
                session()->flash('error', 'Solo el administrador puede mover fondos desde o hacia Tesorería.');
                return;
            }

            // Si intenta crear un ajuste y no tiene permisos
            if ($this->type === 'adjustment' && !$canAdjust) {
                session()->flash('error', 'No tienes permisos para crear ajustes.');
                return;
            }

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
                // Nueva transacción
                if ($this->type === 'transfer') {
                    // Verificar fondos suficientes para transferencias
                    if (!$transactionService->hasSufficientFunds($this->from_account_id, $this->amount)) {
                        session()->flash('error', 'La cuenta origen no tiene fondos suficientes.');
                        return;
                    }

                    $payload = [
                        'type' => $this->type,
                        'from_account_id' => $this->from_account_id,
                        'to_account_id' => $this->to_account_id,
                        'amount' => $this->amount,
                        'description' => $this->description,
                        'notes' => $this->notes,
                        'created_by' => auth()->id(),
                        'status' => 'pending',
                    ];
                } else { // adjustment
                    // Para credit: ingresa dinero al destino. Para debit: descuenta del origen.
                    if ($this->adjustment_direction === 'debit') {
                        if (!$transactionService->hasSufficientFunds($this->from_account_id, $this->amount)) {
                            session()->flash('error', 'La cuenta seleccionada no tiene fondos suficientes.');
                            return;
                        }
                    }

                    $payload = [
                        'type' => $this->type,
                        'from_account_id' => $this->adjustment_direction === 'debit' ? $this->from_account_id : null,
                        'to_account_id' => $this->adjustment_direction === 'credit' ? $this->to_account_id : null,
                        'amount' => $this->amount,
                        'description' => '['.strtoupper($this->adjustment_direction).'] ' . $this->description,
                        'notes' => $this->notes,
                        'created_by' => auth()->id(),
                        'status' => 'pending',
                    ];
                }

                // Create new transaction
                $transactionService->createTransaction($payload);

                session()->flash('message', 'Transacción creada correctamente.');
            }

            return redirect()->route('transactions.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al procesar la transacción: ' . $e->getMessage());
        }
    }

    public function render()
    {
        // Asegurar que exista la cuenta de Tesorería
        $treasury = Account::firstOrCreate(
            [
                'type' => 'treasury',
                'name' => 'Tesorería',
            ],
            [
                'person_id' => null,
                'balance' => 0,
                'notes' => null,
                'is_enabled' => true,
            ]
        );

    $isBoss = $this->isBossUser();
    $canAdjust = $this->canAdjustUser();

        // Listar cuentas: si no es admin, ocultar Tesorería; si es admin, mostrarla primero
        $query = Account::where('is_enabled', true);
        if ($isBoss) {
            $query->orderByRaw("CASE WHEN type = 'treasury' THEN 0 ELSE 1 END");
        } else {
            $query->where('type', '!=', 'treasury');
        }
        $accounts = $query->orderBy('name')->get();

        return view('livewire.transaction-form', [
            'accounts' => $accounts,
            'treasury' => $treasury,
            'isBoss' => $isBoss,
            'canAdjust' => $canAdjust,
        ]);
    }
}
