<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Account;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExpenseService
{
    /**
     * Crea o actualiza una rendición con sus ítems y comprobantes.
     *
     * - Los ítems que vienen con id se actualizan; los nuevos se crean; los que faltan se eliminan.
     * - Los comprobantes existentes se conservan salvo los marcados en remove_documents.
     * - Una rendición rechazada que se corrige vuelve a quedar enviada.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, UploadedFile[]>  $filesByItem  archivos nuevos por índice de ítem
     */
    public function save(?Expense $expense, Account $account, string $description, array $items, array $filesByItem, int $userId): Expense
    {
        return DB::transaction(function () use ($expense, $account, $description, $items, $filesByItem, $userId) {
            if (! $account->person_id) {
                throw new \DomainException('La cuenta seleccionada no tiene una persona asociada.');
            }

            if ($expense) {
                $expense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();
                if (! $expense->isEditable()) {
                    throw new \DomainException('Esta rendición ya fue aprobada y no se puede modificar.');
                }
                $expense->update([
                    'account_id' => $account->id,
                    'submitted_by' => $account->person_id,
                    'description' => $description,
                    ...($expense->status === 'rejected' ? [
                        'status' => 'submitted',
                        'submitted_at' => now(),
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'rejection_reason' => null,
                    ] : []),
                ]);
            } else {
                $expense = Expense::create([
                    'account_id' => $account->id,
                    'submitted_by' => $account->person_id,
                    'description' => $description,
                    'total_amount' => 0,
                    'expense_date' => now()->toDateString(),
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);
            }

            $keptItemIds = [];
            foreach (array_values($items) as $index => $data) {
                $fields = [
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'document_type' => $data['document_type'],
                    'vendor_name' => $data['vendor_name'],
                    'document_number' => $data['receipt_number'] ?? null,
                    'expense_category_id' => $data['expense_category_id'] ?? null,
                    'expense_date' => $data['expense_date'],
                ];

                $item = ! empty($data['id']) ? $expense->items()->whereKey($data['id'])->first() : null;
                if ($item) {
                    $item->update($fields);
                } else {
                    $item = $expense->items()->create($fields);
                }
                $keptItemIds[] = $item->id;

                $toRemove = array_map('intval', $data['remove_documents'] ?? []);
                $item->documents()->whereIn('id', $toRemove)->get()->each(fn (Document $doc) => $this->deleteDocument($doc));

                foreach ($filesByItem[$index] ?? [] as $file) {
                    $this->storeDocument($expense, $item, $file, $userId);
                }
            }

            $expense->items()->whereNotIn('id', $keptItemIds)->get()->each(function (ExpenseItem $item) {
                $item->documents()->get()->each(fn (Document $doc) => $this->deleteDocument($doc));
                $item->delete();
            });

            $expense->update([
                'total_amount' => $expense->items()->sum('amount'),
                // La rendición toma la fecha del comprobante más reciente
                'expense_date' => $expense->items()->max('expense_date'),
            ]);

            return $expense->refresh();
        });
    }

    private function storeDocument(Expense $expense, ExpenseItem $item, UploadedFile $file, int $userId): Document
    {
        $disk = Storage::disk(Document::DISK);
        $dir = "expenses/{$expense->id}/items/{$item->id}";
        $original = $file->getClientOriginalName();
        $name = pathinfo($original, PATHINFO_FILENAME);
        $ext = pathinfo($original, PATHINFO_EXTENSION);

        $candidate = $original;
        for ($n = 1; $disk->exists("$dir/$candidate"); $n++) {
            $candidate = $name . " ($n)" . ($ext ? ".$ext" : '');
        }

        return Document::create([
            'name' => $original,
            'file_path' => $file->storeAs($dir, $candidate, Document::DISK),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'document_type' => $item->document_type,
            'expense_item_id' => $item->id,
            'uploaded_by' => $userId,
            'is_enabled' => true,
        ]);
    }

    private function deleteDocument(Document $document): void
    {
        Storage::disk(Document::DISK)->delete($document->file_path);
        $document->delete();
    }

    /**
     * Crear una nueva rendición de gastos
     */
    public function createExpense(array $data, array $items = []): Expense
    {
        return DB::transaction(function () use ($data, $items) {
            // Generar número de rendición único
            $data['expense_number'] = $this->generateExpenseNumber();

            // Crear la rendición
            $expense = Expense::create($data);

            // Crear items si se proporcionan
            if (!empty($items)) {
                foreach ($items as $item) {
                    $item['expense_id'] = $expense->id;
                    ExpenseItem::create($item);
                }

                // Actualizar total
                $expense->update([
                    'total_amount' => $expense->items()->sum('amount')
                ]);
            }

            Log::info('Rendición creada', [
                'expense_id' => $expense->id,
                'number' => $expense->expense_number,
                'items_count' => count($items)
            ]);

            return $expense;
        });
    }

    /**
     * Enviar rendición para revisión
     */
    public function submitExpense(Expense $expense): bool
    {
        if ($expense->status !== 'draft') {
            throw new \DomainException('Solo se pueden enviar rendiciones en borrador.');
        }

        $expense->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        Log::info('Rendición enviada para revisión', [
            'expense_id' => $expense->id
        ]);

        return true;
    }

    /**
     * Aprobar una rendición
     */
    public function approveExpense(Expense $expense, int $reviewedBy): bool
    {
        DB::transaction(function () use ($expense, $reviewedBy) {
            $locked = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if (!in_array($locked->status, ['submitted', 'reviewed'])) {
                throw new \DomainException('Solo se pueden aprobar rendiciones enviadas o en revisión.');
            }

            $locked->update([
                'status' => 'approved',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
            ]);

            // Rebajar el monto desde la cuenta de la persona (puede quedar negativa)
            $account = Account::lockForUpdate()->find($locked->account_id);
            $account?->decrement('balance', $locked->total_amount);

            Log::info('Rendición aprobada y saldo rebajado', [
                'expense_id' => $locked->id,
                'account_id' => $locked->account_id,
                'amount' => $locked->total_amount,
                'reviewed_by' => $reviewedBy
            ]);
        });

        $expense->refresh();

        return true;
    }

    /**
     * Rechazar una rendición
     */
    public function rejectExpense(Expense $expense, int $reviewedBy, string $reason): bool
    {
        DB::transaction(function () use ($expense, $reviewedBy, $reason) {
            $locked = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if (!in_array($locked->status, ['submitted', 'reviewed'])) {
                throw new \DomainException('Solo se pueden rechazar rendiciones enviadas o en revisión.');
            }

            $locked->update([
                'status' => 'rejected',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            Log::info('Rendición rechazada', [
                'expense_id' => $locked->id,
                'reviewed_by' => $reviewedBy,
                'reason' => $reason
            ]);
        });

        $expense->refresh();

        return true;
    }

    /**
     * Agregar item a una rendición
     */
    public function addExpenseItem(Expense $expense, array $itemData): ExpenseItem
    {
        if ($expense->status !== 'draft') {
            throw new \DomainException('Solo se pueden agregar items a rendiciones en borrador.');
        }

        $itemData['expense_id'] = $expense->id;
        $item = ExpenseItem::create($itemData);

        // Actualizar total de la rendición
        $expense->update([
            'total_amount' => $expense->items()->sum('amount')
        ]);

        return $item;
    }

    /**
     * Generar número único de rendición
     */
    private function generateExpenseNumber(): string
    {
        $year = date('Y');
        if (DB::getDriverName() === 'pgsql') {
            $max = Expense::where('expense_number', 'like', "RND-{$year}-%")
                ->select(DB::raw("MAX(CAST(split_part(expense_number, '-', 3) AS INTEGER)) AS max_suffix"))
                ->value('max_suffix');
        } elseif (DB::getDriverName() === 'mysql') {
            $max = Expense::where('expense_number', 'like', "RND-{$year}-%")
                ->select(DB::raw("MAX(CAST(SUBSTRING_INDEX(expense_number, '-', -1) AS UNSIGNED)) AS max_suffix"))
                ->value('max_suffix');
        } else {
            $max = Expense::where('expense_number', 'like', "RND-{$year}-%")
                ->pluck('expense_number')
                ->map(function ($val) {
                    $parts = explode('-', $val);
                    $suffix = end($parts);
                    return ctype_digit($suffix) ? intval($suffix) : 0;
                })
                ->max();
        }

        $newNumber = intval($max ?? 0) + 1;
        return sprintf('RND-%s-%06d', $year, $newNumber);
    }

    /**
     * Obtener resumen de gastos por período
     */
    public function getExpenseSummary($startDate = null, $endDate = null): array
    {
        $query = Expense::query();

        if ($startDate) {
            $query->whereDate('expense_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('expense_date', '<=', $endDate);
        }

        return [
            'total_expenses' => $query->count(),
            'draft_expenses' => $query->where('status', 'draft')->count(),
            'submitted_expenses' => $query->where('status', 'submitted')->count(),
            'approved_expenses' => $query->where('status', 'approved')->count(),
            'rejected_expenses' => $query->where('status', 'rejected')->count(),
            'total_amount' => $query->where('status', 'approved')->sum('total_amount'),
            'pending_amount' => $query->whereIn('status', ['submitted', 'reviewed'])->sum('total_amount'),
        ];
    }
}
