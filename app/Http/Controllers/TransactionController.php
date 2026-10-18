<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;

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

    public function store(StoreTransactionRequest $request)
    {
        try {
            $transaction = $this->transactionService->createTransaction([
                ...$request->validated(),
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
                    'message' => \App\Support\ErrorMessage::for($e, 'Error al crear la transacción')
                ], 500);
            }
            return redirect()->back()->with('error', \App\Support\ErrorMessage::for($e, 'Error al crear la transacción'));
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
