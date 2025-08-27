<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Account;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses
     */
    public function index(): View
    {
        return view('expenses.index');
    }

    /**
     * Show the form for creating a new expense
     */
    public function create(): View
    {
        // Solo cuentas personales para rendiciones (se rebajará saldo de la persona)
        $accounts = Account::where('is_enabled', true)->where('type', 'person')->get();
        return view('expenses.create', compact('accounts'));
    }

    /**
     * Store a newly created expense
     */
    public function store(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:255',
            'currency' => 'required|in:CLP',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.currency' => 'required|in:CLP',
            'items.*.document_type' => 'required|in:boleta,factura,guia_despacho,ticket,vale',
            'items.*.vendor_name' => 'required|string|max:255',
            'items.*.receipt_number' => 'nullable|string|max:100',
            'items.*.files' => 'nullable|array',
            'items.*.files.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);

        try {
            // Duplicate document validation (in-request and against DB)
            $dupErrors = $this->validateDuplicateDocuments($request->items);
            if (!empty($dupErrors)) {
                $payload = [
                    'success' => false,
                    'message' => 'Se encontraron documentos duplicados (tipo + proveedor + número).',
                    'errors' => $dupErrors,
                ];
                if ($request->wantsJson() || $request->expectsJson() || $request->ajax()) {
                    return response()->json($payload, 422);
                }
                return redirect()->back()->withErrors($dupErrors)->withInput();
            }

            // Calculate total amount
            $totalAmount = collect($request->items)->sum('amount');

            // Derivar la persona desde la cuenta seleccionada (propietario de la cuenta)
            $account = Account::with('person')->findOrFail($request->account_id);
            $person = $account->person;

            if (!$person) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cuenta seleccionada no tiene una persona asociada.'
                ], 400);
            }

            // Create expense
            $expense = Expense::create([
                'account_id' => $request->account_id,
                'description' => $request->description,
                'reference' => $request->reference,
                'total_amount' => $totalAmount,
                'currency' => $request->currency,
                'status' => 'submitted',
                'submitted_by' => $person->id,
                'submitted_at' => now(),
                'expense_date' => now()->toDateString(),
            ]);

            // Create expense items
            foreach ($request->items as $index => $item) {
                $expenseItem = $expense->expenseItems()->create([
                    'description' => $item['description'],
                    'amount' => $item['amount'],
                    'document_type' => $item['document_type'],
                    'vendor_name' => $item['vendor_name'],
                    'expense_date' => now()->toDateString(), // Fecha del gasto
                    'document_number' => $item['receipt_number'] ?? null,
                    'category' => null, // Campo opcional
                ]);

                // Handle attached files for this item (if any)
                $files = $request->file("items.$index.files", []);
                if ($files && is_array($files)) {
                    foreach ($files as $file) {
                        if (!$file) { continue; }
                        $originalName = $file->getClientOriginalName();
                        $mime = $file->getClientMimeType();
                        $size = $file->getSize();
                        // Build storage path keeping human-readable name, avoid overwriting
                        $baseDir = "expenses/{$expense->id}/items/{$expenseItem->id}";
                        $fileName = $this->uniqueFileName($baseDir, $originalName);
                        $storedPath = $file->storeAs($baseDir, $fileName, 'public');

                        Document::create([
                            'name' => $originalName, // keep user's original name
                            'file_path' => $storedPath,
                            'mime_type' => $mime,
                            'file_size' => $size,
                            'document_type' => $expenseItem->document_type,
                            'expense_item_id' => $expenseItem->id,
                            'uploaded_by' => auth()->id(),
                            'is_enabled' => true,
                        ]);
                    }
                }
            }

            $payload = [
                'success' => true,
                'message' => 'Rendición creada correctamente.',
                'expense' => $expense,
            ];

            // Si la petición espera JSON (AJAX/Fetch), devolvemos JSON; de lo contrario redirigimos
            if ($request->wantsJson() || $request->expectsJson() || $request->ajax()) {
                return response()->json($payload);
            }

            return redirect()->route('expenses.index')
                ->with('toastr', [
                    'type' => 'success',
                    'message' => $payload['message'],
                ]);
        } catch (\Exception $e) {
            $message = 'Error al crear la rendición: ' . $e->getMessage();

            if ($request->wantsJson() || $request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 500);
            }

            return redirect()->back()
                ->with('toastr', [
                    'type' => 'error',
                    'message' => $message,
                ])
                ->withInput();
        }
    }

    /**
     * Display the specified expense
     */
    public function show(Expense $expense): View
    {
    $expense->load(['account.person', 'submittedBy', 'items.documents']);
        return view('expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified expense
     */
    public function edit(Expense $expense): View
    {
        $accounts = Account::where('is_enabled', true)->get();
        $expense->load(['items']);
        return view('expenses.edit', compact('expense', 'accounts'));
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:255',
            'currency' => 'required|in:CLP',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.currency' => 'required|in:CLP',
            'items.*.document_type' => 'required|in:boleta,factura,guia_despacho,ticket,vale',
            'items.*.vendor_name' => 'required|string|max:255',
            'items.*.receipt_number' => 'nullable|string|max:100',
            'items.*.files' => 'nullable|array',
            'items.*.files.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);

        try {
            // Duplicate document validation (in-request and against DB, excluding current expense)
            $dupErrors = $this->validateDuplicateDocuments($request->items, $expense->id);
            if (!empty($dupErrors)) {
                return redirect()->back()->withErrors($dupErrors)->withInput();
            }

            // Calculate total amount
            $totalAmount = collect($request->items)->sum('amount');

            // Update expense
            $expense->update([
                'account_id' => $request->account_id,
                'description' => $request->description,
                'reference' => $request->reference,
                'total_amount' => $totalAmount,
            ]);

            // Delete existing items and documents, then recreate
            foreach ($expense->expenseItems as $oldItem) {
                $oldItem->documents()->delete();
            }
            $expense->expenseItems()->delete();

            foreach ($request->items as $index => $item) {
                $expenseItem = $expense->expenseItems()->create([
                    'description' => $item['description'],
                    'amount' => $item['amount'],
                    'document_type' => $item['document_type'],
                    'vendor_name' => $item['vendor_name'],
                    'expense_date' => now()->toDateString(),
                    'document_number' => $item['receipt_number'] ?? null,
                    'category' => null,
                ]);

                $files = $request->file("items.$index.files", []);
                if ($files && is_array($files)) {
                    foreach ($files as $file) {
                        if (!$file) { continue; }
                        $originalName = $file->getClientOriginalName();
                        $mime = $file->getClientMimeType();
                        $size = $file->getSize();
                        $baseDir = "expenses/{$expense->id}/items/{$expenseItem->id}";
                        $fileName = $this->uniqueFileName($baseDir, $originalName);
                        $storedPath = $file->storeAs($baseDir, $fileName, 'public');

                        Document::create([
                            'name' => $originalName,
                            'file_path' => $storedPath,
                            'mime_type' => $mime,
                            'file_size' => $size,
                            'document_type' => $expenseItem->document_type,
                            'expense_item_id' => $expenseItem->id,
                            'uploaded_by' => auth()->id(),
                            'is_enabled' => true,
                        ]);
                    }
                }
            }

            return redirect()->route('expenses.index')
                ->with('toastr', [
                    'type' => 'success',
                    'message' => 'Rendición actualizada correctamente.'
                ]);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('toastr', [
                    'type' => 'error',
                    'message' => 'Error al actualizar la rendición: ' . $e->getMessage()
                ])
                ->withInput();
        }
    }

    /**
     * Remove the specified expense
     */
    public function destroy(Expense $expense)
    {
        try {
            Log::info('Intentando eliminar expense ID: ' . $expense->id);

            $expense->update(['is_enabled' => false]);

            Log::info('Expense eliminado exitosamente: ' . $expense->id);

            return redirect()->route('expenses.index')
                ->with('toastr', [
                    'type' => 'success',
                    'message' => 'Rendición eliminada correctamente.'
                ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar expense: ' . $e->getMessage());

            return redirect()->route('expenses.index')
                ->with('toastr', [
                    'type' => 'error',
                    'message' => 'Error al eliminar la rendición: ' . $e->getMessage()
                ]);
        }
    }

    // Helper methods
    private function uniqueFileName(string $baseDir, string $originalName): string
    {
        $disk = Storage::disk('public');
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $candidate = $originalName;
        $counter = 1;
        while ($disk->exists("$baseDir/$candidate")) {
            $suffix = " ($counter)";
            $candidate = $name . $suffix . ($ext ? ".$ext" : '');
            $counter++;
        }
        return $candidate;
    }

    /**
     * Validate duplicate documents by (document_type, vendor_name, receipt_number)
     * - Detects duplicates inside the current payload
     * - Detects duplicates already stored in DB (globally), optionally excluding an expense ID
     * Returns an array of field errors keyed by dot-notation (items.X.receipt_number)
     */
    private function validateDuplicateDocuments(array $items, ?int $excludeExpenseId = null): array
    {
        $errors = [];

        // Normalize and track in-request duplicates
        $seen = [];
        $comboToIndices = [];
        foreach ($items as $idx => $item) {
            $number = trim((string)($item['receipt_number'] ?? ''));
            if ($number === '') { continue; } // No number -> no duplicate check
            $type = (string)$item['document_type'];
            $vendor = (string)$item['vendor_name'];
            $key = $this->docKey($type, $vendor, $number);
            $comboToIndices[$key] = $comboToIndices[$key] ?? [];
            $comboToIndices[$key][] = (int)$idx;
            if (isset($seen[$key])) {
                // Duplicate within the same request
                $errors["items.$idx.receipt_number"] = [
                    'Documento duplicado dentro de la rendición (mismo tipo, proveedor y número).',
                ];
                // Also mark the first occurrence to guide the user
                $firstIdx = $seen[$key];
                $errors["items.$firstIdx.receipt_number"] = $errors["items.$firstIdx.receipt_number"] ?? [
                    'Documento duplicado dentro de la rendición.',
                ];
            } else {
                $seen[$key] = (int)$idx;
            }
        }

        // Check against DB only if we have any combos
        if (!empty($comboToIndices)) {
            $existing = ExpenseItem::query()
                ->select(['document_type', 'vendor_name', 'document_number', 'expense_id'])
                ->whereNotNull('document_number')
                ->when($excludeExpenseId !== null, function ($q) use ($excludeExpenseId) {
                    $q->where('expense_id', '!=', $excludeExpenseId);
                })
                ->where(function ($q) use ($comboToIndices) {
                    foreach (array_keys($comboToIndices) as $key) {
                        [$type, $vendorNorm, $numberNorm] = explode('|', $key);
                        $q->orWhere(function ($qq) use ($type, $vendorNorm, $numberNorm) {
                            $qq->where('document_type', $type)
                                ->whereRaw('LOWER(TRIM(vendor_name)) = ?', [$vendorNorm])
                                ->whereRaw('LOWER(TRIM(document_number)) = ?', [$numberNorm]);
                        });
                    }
                })
                ->limit(50)
                ->get();

            foreach ($existing as $row) {
                $key = $this->docKey($row->document_type, $row->vendor_name, $row->document_number);
                if (!isset($comboToIndices[$key])) { continue; }
                foreach ($comboToIndices[$key] as $idx) {
                    $errors["items.$idx.receipt_number"] = $errors["items.$idx.receipt_number"] ?? [];
                    $errors["items.$idx.receipt_number"][] = 'Documento ya registrado en otra rendición (mismo tipo, proveedor y número).';
                }
            }
        }

        return $errors;
    }

    private function docKey(string $type, string $vendor, string $number): string
    {
        $normVendor = mb_strtolower(trim(preg_replace('/\s+/', ' ', $vendor)), 'UTF-8');
        $normNumber = mb_strtolower(trim($number), 'UTF-8');
        return $type . '|' . $normVendor . '|' . $normNumber;
    }
}
