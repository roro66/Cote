<?php

namespace App\Livewire;

use App\Models\Expense;
use App\Services\ExpenseService;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Revisión de rendiciones de a una: la boleta grande, los datos al lado y dos botones.
 */
class ExpenseReview extends Component
{
    public const REASONS = [
        'Falta la boleta',
        'La foto no se lee',
        'El monto no coincide con la boleta',
        'La fecha no corresponde',
        'Falta explicar en qué se gastó',
    ];

    /** @var list<int> */
    #[Locked]
    public array $skipped = [];

    public bool $returning = false;

    public string $reason = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['boss', 'treasurer']), 403);
    }

    public function approve(int $id, ExpenseService $service): void
    {
        try {
            $service->approveExpense(Expense::findOrFail($id), auth()->id());
            $this->dispatch('cote-toast', type: 'success', message: 'Aprobada. El saldo ya se actualizó.');
        } catch (\DomainException $e) {
            $this->dispatch('cote-toast', type: 'error', message: $e->getMessage());
        }
        $this->resetReturn();
    }

    public function startReturn(): void
    {
        $this->returning = true;
        $this->reason = '';
    }

    public function addReason(string $text): void
    {
        $this->reason = trim($this->reason === '' ? $text : $this->reason.'. '.$text);
    }

    public function sendBack(int $id, ExpenseService $service): void
    {
        $reason = mb_substr(trim($this->reason), 0, 500);
        if ($reason === '') {
            $this->addError('reason', 'Elige o escribe qué debe corregir.');

            return;
        }

        try {
            $service->rejectExpense(Expense::findOrFail($id), auth()->id(), $reason);
            $this->dispatch('cote-toast', type: 'success', message: 'Devuelta para corregir. El trabajador verá el motivo.');
        } catch (\DomainException $e) {
            $this->dispatch('cote-toast', type: 'error', message: $e->getMessage());
        }
        $this->resetReturn();
    }

    public function skip(int $id): void
    {
        $this->skipped[] = $id;
        $this->resetReturn();
    }

    public function restartSkipped(): void
    {
        $this->skipped = [];
    }

    private function resetReturn(): void
    {
        $this->returning = false;
        $this->reason = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $pending = Expense::where('status', 'submitted')->where('is_enabled', true);
        $current = (clone $pending)
            ->whereNotIn('id', $this->skipped)
            ->with(['account', 'submittedBy', 'items.categoryObj', 'items.documents' => fn ($q) => $q->where('is_enabled', true)])
            ->orderBy('submitted_at')->orderBy('id')
            ->first();

        return view('livewire.expense-review', [
            'current' => $current,
            'remaining' => $pending->count(),
            'reasons' => self::REASONS,
        ]);
    }
}
