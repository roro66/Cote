<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Person;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        return view('accounts.index');
    }

    public function create()
    {
        $people = Person::where('is_enabled', true)->get();
        return view('accounts.create', compact('people'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:treasury,person',
            'person_id' => 'nullable|required_if:type,person|exists:people,id',
            'balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'is_enabled' => 'in:0,1',
        ]);

        $data = $request->only(['name', 'type', 'person_id', 'balance', 'notes']);
        $data['is_enabled'] = $request->input('is_enabled', 0);

        $account = Account::create($data);

        // Si es una petición AJAX, devolver JSON
        if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'success' => true,
                'message' => 'Cuenta creada exitosamente',
                'account' => $account->load('person')
            ]);
        }

        return redirect()->route('accounts.index')->with('success', 'Cuenta creada exitosamente');
    }

    public function show(Account $account)
    {
        return response()->json($account->load('person'));
    }

    public function edit(Account $account)
    {
        $people = Person::where('is_enabled', true)->get();
        return view('accounts.edit', compact('account', 'people'));
    }

    public function update(Request $request, Account $account)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:treasury,person',
            'person_id' => 'nullable|required_if:type,person|exists:people,id',
            'balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'is_enabled' => 'in:0,1',
        ]);

        $data = $request->only(['name', 'type', 'person_id', 'balance', 'notes']);
        $data['is_enabled'] = $request->input('is_enabled', 0);

        $account->update($data);

        return redirect()->route('accounts.index')->with('success', 'Cuenta actualizada exitosamente');
    }

    public function destroy(Account $account)
    {
        // Verificar si la cuenta tiene transacciones asociadas (desde o hacia esta cuenta)
        $transactionCount = $account->transactionsFrom()->count() + $account->transactionsTo()->count();
        
        if ($transactionCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la cuenta porque tiene transacciones asociadas'
            ], 400);
        }

        // Verificar si la cuenta tiene gastos asociados
        if ($account->expenses()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la cuenta porque tiene gastos asociados'
            ], 400);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta eliminada exitosamente'
        ]);
    }
}
