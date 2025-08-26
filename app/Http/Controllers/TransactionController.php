<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Account;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index()
    {
        return view('transactions.index');
    }

    public function create()
    {
        return view('transactions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:transfer',
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string',
        ]);

        try {
            // Restringir transferencias sólo entre Tesorería y Persona (ambos sentidos)
            $from = Account::find($request->from_account_id);
            $to = Account::find($request->to_account_id);
            if (!($from && $to)) {
                return back()->withErrors(['from_account_id' => 'Cuentas inválidas.']);
            }
            $isTreasuryToPerson = $from->type === 'treasury' && $to->type === 'person';
            $isPersonToTreasury = $from->type === 'person' && $to->type === 'treasury';
            if (!($isTreasuryToPerson || $isPersonToTreasury)) {
                $msg = 'Solo se permiten transferencias entre Tesorería y cuentas personales.';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withErrors(['to_account_id' => $msg]);
            }

            // Verificar fondos suficientes para transferencias
            if ($request->type === 'transfer' && !$this->transactionService->hasSufficientFunds($request->from_account_id, $request->amount)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La cuenta origen no tiene fondos suficientes.'
                    ], 400);
                }
                return redirect()->back()->withErrors(['amount' => 'La cuenta origen no tiene fondos suficientes.']);
            }

            // Crear la transacción
            $transaction = $this->transactionService->createTransaction([
                'type' => $request->type,
                'from_account_id' => $request->from_account_id,
                'to_account_id' => $request->to_account_id,
                'amount' => $request->amount,
                'description' => $request->description,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
                'status' => 'pending',
            ]);

            // Respuesta JSON para AJAX
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => true,
                    'message' => 'Transacción creada exitosamente',
                    'transaction' => $transaction->load(['fromAccount', 'toAccount'])
                ]);
            }

            return redirect()->route('transactions.index')->with('success', 'Transacción creada exitosamente');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la transacción: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error al crear la transacción: ' . $e->getMessage());
        }
    }

    public function show(Transaction $transaction)
    {
        // Cargar las relaciones necesarias
        $transaction->load(['fromAccount', 'toAccount', 'createdBy', 'approvedBy']);

        return view('transactions.show', compact('transaction'));
    }

    public function edit($id)
    {
        return view('transactions.edit', compact('id'));
    }
}
