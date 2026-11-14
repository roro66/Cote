<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Services\ExpenseService;
use App\Support\DuplicateReceipts;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Registrar la rendición que el trabajador entrega en papel: planilla llenada a mano y boletas
 * pegadas en hojas. El tesorero sube las fotos y transcribe la planilla fila por fila; al guardar
 * queda aprobada y se descuenta del saldo del trabajador.
 */
class ExpenseIntake extends Component
{
    use WithFileUploads;

    public const TYPES = ['boleta' => 'Boleta', 'factura' => 'Factura', 'ticket' => 'Ticket', 'vale' => 'Vale', 'guia_despacho' => 'Guía de despacho'];

    public string $step = 'persona';

    public string $search = '';

    public ?int $accountId = null;

    public string $date = '';

    public string $description = '';

    public string $declaredTotal = '';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newPhotos = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $photos = [];

    /** @var list<int> */
    public array $removePhotoIds = [];

    #[Locked]
    public ?int $expenseId = null;

    #[Locked]
    public ?int $doneId = null;

    public function mount(?Expense $expense = null, ?int $persona = null): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['boss', 'treasurer']), 403);
        $this->date = now()->toDateString();

        if ($expense?->exists) {
            abort_unless($expense->isReceived() && $expense->isEditable(), 404);
            $this->expenseId = $expense->id;
            $this->accountId = $expense->account_id;
            $this->date = ($expense->submitted_at ?? now())->toDateString();
            $this->description = (string) $expense->description;
            $this->declaredTotal = $expense->declared_total ? (string) (int) $expense->declared_total : '';
            $this->rows = $expense->items()->orderBy('id')->get()->map(fn (ExpenseItem $item) => [
                'date' => $item->expense_date?->toDateString() ?? $this->date,
                'category_id' => (string) $item->expense_category_id,
                'project' => (string) $item->project,
                'city' => (string) $item->city,
                'type' => $item->document_type,
                'vendor' => $item->vendor_name,
                'number' => (string) $item->document_number,
                'amount' => (string) (int) $item->amount,
            ])->all();
            $this->step = 'planilla';

            return;
        }

        if ($persona && ($account = $this->workerAccounts()->firstWhere('person_id', $persona))) {
            $this->choose($account->id);
        }
    }

    public function choose(int $accountId): void
    {
        abort_unless($this->workerAccounts()->contains('id', $accountId), 404);
        $this->accountId = $accountId;
        if ($this->rows === []) {
            $this->rows = [$this->blankRow()];
        }
        $this->step = 'planilla';
    }

    public function changeWorker(): void
    {
        abort_if($this->expenseId !== null, 403);
        $this->step = 'persona';
    }

    /** Fila nueva con la fecha, proyecto, ciudad y tipo de la anterior: en la planilla se repiten con comillas. */
    public function addRow(): void
    {
        $this->rows[] = $this->blankRow(end($this->rows) ?: null);
        $this->dispatch('intake-row-added', index: count($this->rows) - 1);
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
        if ($this->rows === []) {
            $this->rows = [$this->blankRow()];
        }
        $this->resetErrorBag();
    }

    public function updatedNewPhotos(): void
    {
        $this->validate([
            'newPhotos.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], [
            'newPhotos.*.mimes' => 'Las fotos deben ser JPG, PNG, WEBP o PDF.',
            'newPhotos.*.max' => 'Cada foto puede pesar como máximo 10 MB.',
        ]);
        $this->photos = [...$this->photos, ...$this->newPhotos];
        $this->newPhotos = [];
    }

    public function removeNewPhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function removeSavedPhoto(int $id): void
    {
        $this->removePhotoIds[] = $id;
    }

    public function save(ExpenseService $expenses): void
    {
        $account = $this->workerAccounts()->firstWhere('id', $this->accountId);
        abort_unless($account, 404);

        $this->rows = array_values(array_filter($this->rows, fn ($r) => trim(implode('', array_map('strval', $r))) !== '' && ! $this->isUntouched($r)));
        if ($this->rows === []) {
            $this->rows = [$this->blankRow()];
            $this->addError('rows', 'Escribe al menos un gasto.');

            return;
        }

        $this->validate([
            'date' => 'required|date|before_or_equal:today',
            'description' => 'nullable|string|max:500',
            'declaredTotal' => 'nullable|numeric|min:0',
            'rows.*.date' => 'required|date|before_or_equal:today',
            'rows.*.category_id' => 'required|exists:expense_categories,id',
            'rows.*.project' => 'nullable|string|max:150',
            'rows.*.city' => 'nullable|string|max:100',
            'rows.*.type' => 'required|in:' . implode(',', array_keys(self::TYPES)),
            'rows.*.vendor' => 'required|string|max:255',
            'rows.*.number' => 'required|string|max:100',
            'rows.*.amount' => 'required|integer|min:1',
        ], [
            'date.before_or_equal' => 'La fecha de la rendición no puede ser posterior a hoy.',
            'rows.*.date.required' => 'Falta la fecha.',
            'rows.*.date.before_or_equal' => 'La fecha no puede ser posterior a hoy.',
            'rows.*.category_id.required' => 'Elige el concepto.',
            'rows.*.vendor.required' => 'Falta el proveedor.',
            'rows.*.number.required' => 'Falta el N°.',
            'rows.*.amount.required' => 'Falta el monto.',
            'rows.*.amount.min' => 'El monto debe ser mayor a cero.',
            'rows.*.amount.integer' => 'Escribe el monto sin decimales.',
        ]);

        $duplicates = DuplicateReceipts::find(array_map(fn ($r) => [
            'document_type' => $r['type'], 'vendor_name' => $r['vendor'], 'receipt_number' => $r['number'],
        ], $this->rows), $this->expenseId);
        foreach ($duplicates as $index => $messages) {
            $this->addError("rows.$index.number", $messages[0]);
        }
        $keptPhotos = $this->expenseId ? Expense::find($this->expenseId)->photos()->whereNotIn('id', $this->removePhotoIds)->count() : 0;
        if ($keptPhotos + count($this->photos) === 0) {
            $this->addError('photos', 'Sube al menos una foto de la rendición (la planilla y las hojas con boletas).');
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        try {
            $expense = $expenses->registerReceived(
                $this->expenseId ? Expense::find($this->expenseId) : null,
                $account,
                ['date' => $this->date, 'description' => $this->description, 'declared_total' => $this->declaredTotal !== '' ? (int) $this->declaredTotal : null],
                array_map(fn ($r) => [...$r, 'category_id' => (int) $r['category_id'], 'amount' => (int) $r['amount'], 'vendor' => trim($r['vendor']), 'number' => trim($r['number']), 'project' => trim((string) $r['project']), 'city' => trim((string) $r['city'])], $this->rows),
                array_values($this->photos),
                $this->removePhotoIds,
                auth()->id(),
            );
        } catch (\DomainException $e) {
            $this->addError('rows', $e->getMessage());

            return;
        }

        $this->doneId = $expense->id;
        $this->step = 'listo';
    }

    public function restart(): void
    {
        $this->reset(['step', 'search', 'accountId', 'description', 'declaredTotal', 'rows', 'newPhotos', 'photos', 'removePhotoIds', 'expenseId', 'doneId']);
        $this->date = now()->toDateString();
    }

    /** @return array<string, mixed> */
    private function blankRow(?array $previous = null): array
    {
        return [
            'date' => $previous['date'] ?? $this->date,
            'category_id' => '',
            'project' => $previous['project'] ?? '',
            'city' => $previous['city'] ?? '',
            'type' => $previous['type'] ?? 'boleta',
            'vendor' => '',
            'number' => '',
            'amount' => '',
        ];
    }

    /** Una fila agregada y no llenada solo trae lo copiado de la anterior. */
    private function isUntouched(array $row): bool
    {
        return $row['category_id'] === '' && trim((string) $row['vendor']) === '' && trim((string) $row['number']) === '' && (string) $row['amount'] === '';
    }

    private function workerAccounts()
    {
        return Account::with('person')
            ->where('type', 'person')
            ->whereNotNull('person_id')
            ->where(fn ($q) => $q->where('is_enabled', true)->when($this->expenseId, fn ($qq) => $qq->orWhere('id', $this->accountId)))
            ->get()
            ->sortBy(fn (Account $a) => Str::lower($a->person?->full_name ?? $a->name))
            ->values();
    }

    /** @return list<string> */
    private function suggestions(string $column): array
    {
        return ExpenseItem::query()->whereNotNull($column)->where($column, '!=', '')
            ->selectRaw("$column as value, count(*) as uses")->groupBy($column)
            ->orderByDesc('uses')->limit(80)->pluck('value')->all();
    }

    public function render()
    {
        $search = Str::lower(Str::ascii(trim($this->search)));
        $account = $this->accountId ? Account::with('person')->find($this->accountId) : null;
        $total = collect($this->rows)->sum(fn ($r) => (int) ($r['amount'] ?: 0));

        return view('livewire.expense-intake', [
            'workers' => $this->step === 'persona'
                ? $this->workerAccounts()->filter(fn (Account $a) => $search === '' || str_contains(Str::lower(Str::ascii($a->person?->full_name ?? $a->name)), $search))
                : collect(),
            'account' => $account,
            'categories' => ExpenseCategory::where('is_enabled', true)->orderBy('name')->get(['id', 'name']),
            'projects' => $this->step === 'planilla' ? $this->suggestions('project') : [],
            'cities' => $this->step === 'planilla' ? $this->suggestions('city') : [],
            'vendors' => $this->step === 'planilla' ? $this->suggestions('vendor_name') : [],
            'savedPhotos' => $this->expenseId ? Expense::find($this->expenseId)->photos()->whereNotIn('id', $this->removePhotoIds)->get() : collect(),
            'subtotalFactura' => collect($this->rows)->where('type', 'factura')->sum(fn ($r) => (int) ($r['amount'] ?: 0)),
            'total' => $total,
            'done' => $this->doneId ? Expense::with('account.person')->find($this->doneId) : null,
            'today' => Carbon::today()->toDateString(),
        ]);
    }
}
