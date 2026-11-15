<?php

namespace App\Livewire;

use App\Livewire\Concerns\RestoresAutosavedDraft;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Entregar dinero, recibir una devolución o registrar dinero que entra a la caja, paso a paso
 * y con una confirmación en palabras. Lo que se guarda queda aprobado de inmediato.
 */
class CashMovement extends Component
{
    use RestoresAutosavedDraft;

    public const MODES = ['entregar', 'devolucion', 'ingreso'];

    #[Locked]
    public string $mode = 'entregar';

    public string $step = 'persona';

    public ?int $accountId = null;

    public string $amount = '';

    public string $description = '';

    public string $search = '';

    #[Locked]
    public ?int $doneId = null;

    public function mount(string $mode, ?int $persona = null, ?string $monto = null, ?string $detalle = null): void
    {
        abort_unless(in_array($mode, self::MODES, true), 404);
        abort_unless(auth()->user()?->hasAnyRole(['boss', 'treasurer']), 403);

        $this->mode = $mode;
        $this->amount = preg_replace('/\D/', '', (string) $monto) ?? '';
        $this->description = mb_substr(trim((string) $detalle), 0, 500);

        if ($mode === 'ingreso') {
            $this->step = 'monto';

            return;
        }

        if ($persona) {
            $account = $this->workerAccounts()->firstWhere('person_id', $persona);
            if ($account) {
                $this->accountId = $account->id;
                $this->step = $this->amount !== '' ? ($this->description !== '' ? 'confirmar' : 'detalle') : 'monto';
            }
        }
    }

    public function choose(int $accountId): void
    {
        abort_unless($this->workerAccounts()->contains('id', $accountId), 404);
        $this->accountId = $accountId;
        $this->resetErrorBag();
        $this->step = 'monto';
    }

    public function useAmount(int $amount): void
    {
        $this->amount = (string) max(0, $amount);
    }

    public function toDetail(): void
    {
        $this->resetErrorBag();
        $amount = (int) $this->amount;
        if ($amount < 1) {
            $this->addError('amount', 'Escribe cuánto dinero es.');

            return;
        }
        if ($message = $this->amountProblem($amount)) {
            $this->addError('amount', $message);

            return;
        }
        if ($this->description === '') {
            $this->description = match ($this->mode) {
                'entregar' => 'Entrega de dinero para gastos',
                'devolucion' => 'Devolución de dinero que le sobró',
                default => '',
            };
        }
        $this->step = 'detalle';
    }

    public function useDetail(string $text): void
    {
        $this->description = mb_substr($text, 0, 500);
    }

    public function toConfirm(): void
    {
        $this->resetErrorBag();
        $this->description = trim($this->description);
        if ($this->description === '') {
            $this->addError('description', $this->mode === 'ingreso' ? 'Escribe de dónde viene el dinero.' : 'Escribe un detalle corto.');

            return;
        }
        $this->step = 'confirmar';
    }

    public function back(): void
    {
        $order = $this->mode === 'ingreso' ? ['monto', 'detalle', 'confirmar'] : ['persona', 'monto', 'detalle', 'confirmar'];
        $index = array_search($this->step, $order, true);
        $this->step = $order[max(0, (int) $index - 1)];
        $this->resetErrorBag();
    }

    public function save(TransactionService $service): void
    {
        $amount = (int) $this->amount;
        $treasury = $this->treasury();
        if (! $treasury || $amount < 1 || ($this->mode !== 'ingreso' && ! $this->account())) {
            $this->step = $this->mode === 'ingreso' ? 'monto' : 'persona';

            return;
        }
        if ($message = $this->amountProblem($amount)) {
            $this->addError('amount', $message);
            $this->step = 'monto';

            return;
        }

        $data = match ($this->mode) {
            'entregar' => ['type' => 'transfer', 'from_account_id' => $treasury->id, 'to_account_id' => $this->accountId],
            'devolucion' => ['type' => 'transfer', 'from_account_id' => $this->accountId, 'to_account_id' => $treasury->id],
            default => ['type' => 'deposit', 'from_account_id' => null, 'to_account_id' => $treasury->id],
        };

        try {
            $transaction = $service->createApproved([...$data, 'amount' => $amount, 'description' => $this->description], auth()->id());
            $this->doneId = $transaction->id;
            $this->step = 'listo';
            // Después de "listo": si se limpia antes, el morph puede volver a autoguardar el formulario.
            $this->clearClientAutosave();
        } catch (\DomainException $e) {
            $this->addError('amount', $e->getMessage());
            $this->step = 'monto';
        }
    }

    /** @param  array<string, mixed>  $savedData */
    public function restoreSavedData(array $savedData): void
    {
        try {
            $this->applyAutosavedProperties($savedData, ['accountId', 'amount', 'description', 'step', 'search']);
            if ($this->mode === 'ingreso' && $this->step === 'persona') {
                $this->step = 'monto';
            }
            if ($this->mode !== 'ingreso' && ! $this->accountId && $this->step !== 'persona') {
                $this->step = 'persona';
            }
            $this->notifyAutosaveRestored();
        } catch (\Throwable) {
            $this->notifyAutosaveRestoreFailed();
        }
    }

    public function annulDone(string $reason, TransactionService $service): void
    {
        $transaction = $this->doneId ? Transaction::find($this->doneId) : null;
        if (! $transaction || trim($reason) === '') {
            return;
        }

        try {
            $service->annulTransaction($transaction, auth()->id(), mb_substr(trim($reason), 0, 500));
            $this->dispatch('cote-toast', type: 'success', message: 'Listo, quedó anulado. Los saldos volvieron a como estaban.');
            $this->restart();
        } catch (\DomainException $e) {
            $this->dispatch('cote-toast', type: 'error', message: $e->getMessage());
        }
    }

    public function restart(): void
    {
        $this->clearClientAutosave();
        $this->reset(['accountId', 'amount', 'description', 'search', 'doneId']);
        $this->step = $this->mode === 'ingreso' ? 'monto' : 'persona';
        $this->resetErrorBag();
    }

    private function amountProblem(int $amount): ?string
    {
        $money = fn ($value) => '$'.number_format((float) $value, 0, ',', '.');

        if ($this->mode === 'entregar') {
            $available = (float) ($this->treasury()?->balance ?? 0);
            if ($amount > $available) {
                return "La caja solo tiene {$money($available)}. Primero registra el dinero que entró a la caja.";
            }
        }
        if ($this->mode === 'devolucion') {
            $account = $this->account();
            $holding = max(0, (float) ($account?->balance ?? 0));
            if ($amount > $holding) {
                $name = $account?->person?->first_name ?? 'Esta persona';

                return $holding > 0
                    ? "{$name} solo tiene {$money($holding)} en su poder."
                    : "{$name} no tiene dinero de la empresa en su poder.";
            }
        }

        return null;
    }

    private function treasury(): ?Account
    {
        return Account::where('type', 'treasury')->first();
    }

    private function account(): ?Account
    {
        return $this->accountId ? $this->workerAccounts()->firstWhere('id', $this->accountId) : null;
    }

    /** @return \Illuminate\Support\Collection<int, Account> */
    private function workerAccounts()
    {
        return Account::with('person')
            ->where('type', 'person')
            ->where('is_enabled', true)
            ->whereNotNull('person_id')
            ->get()
            ->sortBy(fn (Account $a) => Str::lower($a->person?->full_name ?? $a->name))
            ->values();
    }

    public function render()
    {
        $search = Str::lower(Str::ascii(trim($this->search)));
        $workers = $this->workerAccounts()->filter(fn (Account $a) => $search === ''
            || str_contains(Str::lower(Str::ascii($a->person?->full_name ?? $a->name)), $search));

        return view('livewire.cash-movement', [
            'treasury' => $this->treasury(),
            'account' => $this->account(),
            'workers' => $workers,
            'done' => $this->doneId ? Transaction::with(['fromAccount.person', 'toAccount.person'])->find($this->doneId) : null,
        ]);
    }
}
