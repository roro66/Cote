<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Account;
use App\Services\ExpenseService;
use App\Support\ErrorMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $expenses)
    {
    }

    public function index(): View
    {
        return view('expenses.index');
    }

    public function create(): View
    {
        return view('expenses.create', ['accounts' => $this->accountsForForm()]);
    }

    public function store(Request $request)
    {
        $this->validateExpense($request);
        $account = Account::findOrFail($request->account_id);
        $this->authorize('view', $account);

        if ($errors = $this->validateReceipts($request)) {
            return $this->errorsResponse($request, $errors, 'Falta el comprobante de uno o más gastos.');
        }
        if ($errors = $this->validateDuplicateDocuments($request->items)) {
            return $this->errorsResponse($request, $errors, 'Se encontraron documentos duplicados (tipo + proveedor + número).');
        }

        try {
            $expense = $this->expenses->save(null, $account, $request->description, $request->items, $this->filesByItem($request), auth()->id());

            return $this->savedResponse($request, $expense, 'Rendición creada correctamente.');
        } catch (\Throwable $e) {
            return $this->failedResponse($request, ErrorMessage::for($e, 'Error al crear la rendición'));
        }
    }

    public function show(Expense $expense): View
    {
        $this->authorize('view', $expense);
        $expense->load(['account.person', 'submittedBy', 'items.documents', 'items.categoryObj', 'photos', 'registeredBy']);

        return view('expenses.show', compact('expense'));
    }

    /**
     * Rendición en PDF para imprimir y firmar, con las imágenes de los comprobantes
     */
    public function pdf(Expense $expense, \App\Services\ExpensePdfService $pdfs)
    {
        $this->authorize('view', $expense);

        return $pdfs->download($expense);
    }

    /**
     * Detalle como fragmento HTML para el modal de aprobaciones
     */
    public function details(Expense $expense): JsonResponse
    {
        $this->authorize('view', $expense);

        try {
            $expense->load(['account.person', 'submittedBy', 'items.documents']);
            $html = view('expenses.partials.details_modal', compact('expense'))->render();

            return response()->json(['success' => true, 'html' => $html]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => ErrorMessage::for($e, 'Error al renderizar detalles')], 500);
        }
    }

    public function edit(Expense $expense): View|\Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $expense);
        if ($expense->isReceived() && auth()->user()->hasAnyRole(['boss', 'treasurer'])) {
            return redirect()->route('expenses.intake.edit', $expense);
        }
        $expense->load('items.documents');

        return view('expenses.edit', ['expense' => $expense, 'accounts' => $this->accountsForForm()]);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense);
        $this->validateExpense($request);
        $account = Account::findOrFail($request->account_id);
        $this->authorize('view', $account);

        if ($errors = $this->validateReceipts($request, $expense)) {
            return $this->errorsResponse($request, $errors, 'Falta el comprobante de uno o más gastos.');
        }
        if ($errors = $this->validateDuplicateDocuments($request->items, $expense->id)) {
            return $this->errorsResponse($request, $errors, 'Se encontraron documentos duplicados (tipo + proveedor + número).');
        }

        try {
            $expense = $this->expenses->save($expense, $account, $request->description, $request->items, $this->filesByItem($request), auth()->id());

            return $this->savedResponse($request, $expense, 'Rendición actualizada correctamente.');
        } catch (\Throwable $e) {
            return $this->failedResponse($request, ErrorMessage::for($e, 'Error al actualizar la rendición'));
        }
    }

    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);

        try {
            $expense->update(['is_enabled' => false]);
            Log::info('Rendición eliminada', ['expense_id' => $expense->id, 'user_id' => auth()->id()]);

            return redirect()->route('expenses.index')
                ->with('toastr', ['type' => 'success', 'message' => 'Rendición eliminada correctamente.']);
        } catch (\Exception $e) {
            return redirect()->route('expenses.index')
                ->with('toastr', ['type' => 'error', 'message' => ErrorMessage::for($e, 'Error al eliminar la rendición')]);
        }
    }

    private function accountsForForm()
    {
        return Account::visibleTo(auth()->user())
            ->with('person')
            ->where('is_enabled', true)
            ->where('type', 'person')
            ->whereNotNull('person_id')
            ->get();
    }

    private function validateExpense(Request $request): void
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'description' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.expense_date' => 'required|date|before_or_equal:today',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:1',
            'items.*.document_type' => 'required|in:boleta,factura,guia_despacho,ticket,vale',
            'items.*.vendor_name' => 'nullable|string|max:255',
            'items.*.receipt_number' => 'nullable|string|max:100',
            'items.*.expense_category_id' => 'nullable|exists:expense_categories,id',
            'items.*.without_receipt' => 'nullable|boolean',
            'items.*.without_receipt_reason' => 'nullable|string|max:255',
            'items.*.remove_documents' => 'nullable|array',
            'items.*.remove_documents.*' => 'integer',
            'items.*.files' => 'nullable|array',
            'items.*.files.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);

        $vendorErrors = [];
        foreach ($request->input('items', []) as $key => $item) {
            if (\App\Models\ExpenseCategory::allowsBlankVendorId($item['expense_category_id'] ?? null)) {
                continue;
            }
            if (trim((string) ($item['vendor_name'] ?? '')) === '') {
                $vendorErrors["items.$key.vendor_name"] = ['Falta el proveedor.'];
            }
        }
        if ($vendorErrors !== []) {
            throw \Illuminate\Validation\ValidationException::withMessages($vendorErrors);
        }
    }

    /** @return array<int, \Illuminate\Http\UploadedFile[]> archivos nuevos por posición de ítem */
    private function filesByItem(Request $request): array
    {
        $files = [];
        foreach (array_keys($request->items) as $position => $key) {
            $files[$position] = array_filter((array) $request->file("items.$key.files", []));
        }

        return $files;
    }

    private function savedResponse(Request $request, Expense $expense, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message, 'redirect' => route('expenses.show', $expense)]);
        }

        return redirect()->route('expenses.show', $expense)->with('toastr', ['type' => 'success', 'message' => $message]);
    }

    private function failedResponse(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return redirect()->back()->with('toastr', ['type' => 'error', 'message' => $message])->withInput();
    }

    /**
     * Cada gasto necesita al menos un comprobante (los que quedan + los nuevos),
     * salvo que se marque "sin comprobante" con un motivo.
     */
    private function validateReceipts(Request $request, ?Expense $expense = null): array
    {
        $errors = [];
        foreach ($request->items as $key => $item) {
            // Viáticos: no hay boleta de un comercio concreto.
            if (\App\Models\ExpenseCategory::allowsBlankVendorId($item['expense_category_id'] ?? null)) {
                continue;
            }

            if (ExpenseService::isWithoutReceipt($item)) {
                if (trim((string) ($item['without_receipt_reason'] ?? '')) === '') {
                    $errors["items.$key.without_receipt_reason"] = ['Indica por qué este gasto no tiene comprobante.'];
                }
                continue;
            }

            $kept = 0;
            if ($expense && ! empty($item['id'])) {
                $kept = Document::where('expense_item_id', $item['id'])
                    ->whereHas('expenseItem', fn ($q) => $q->where('expense_id', $expense->id))
                    ->whereNotIn('id', array_map('intval', $item['remove_documents'] ?? []))
                    ->count();
            }
            $new = count(array_filter((array) $request->file("items.$key.files", [])));

            if ($kept + $new === 0) {
                $errors["items.$key.files"] = ['Adjunta al menos un comprobante, o marca "Sin comprobante" e indica el motivo.'];
            }
        }

        return $errors;
    }

    private function errorsResponse(Request $request, array $errors, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], 422);
        }

        return redirect()->back()->withErrors($errors)->withInput();
    }

    /** @return array<string, list<string>> errores por campo (items.X.receipt_number) */
    private function validateDuplicateDocuments(array $items, ?int $excludeExpenseId = null): array
    {
        return collect(\App\Support\DuplicateReceipts::find($items, $excludeExpenseId))
            ->mapWithKeys(fn ($messages, $idx) => ["items.$idx.receipt_number" => $messages])
            ->all();
    }
}
