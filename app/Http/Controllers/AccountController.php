<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Person;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        $people = Account::where('type', 'person')->where('is_enabled', true);

        $stats = [
            'total' => Account::count(),
            'enabled' => Account::where('is_enabled', true)->count(),
            'treasury_balance' => (float) Account::where('type', 'treasury')->sum('balance'),
            // Dinero de la empresa en manos de trabajadores (deben rendirlo o devolverlo)
            'to_render' => (float) (clone $people)->where('balance', '>', 0)->sum('balance'),
            // Trabajadores que gastaron de su bolsillo: la empresa les debe
            'owed' => (float) abs((clone $people)->where('balance', '<', 0)->sum('balance')),
        ];

        return view('accounts.index', compact('stats'));
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
            'notes' => 'nullable|string',
            'is_enabled' => 'in:0,1',
        ]);

        // Toda cuenta nace en $0: el dinero entra por ingresos, transferencias o ajustes auditados
        $data = $request->only(['name', 'type', 'person_id', 'notes']);
        $data['balance'] = 0;
        // If this is a personal account and no name provided, default to the owner's full name
        if (($data['type'] ?? '') === 'person' && empty($data['name']) && !empty($data['person_id'])) {
            $person = Person::find($data['person_id']);
            if ($person) {
                $data['name'] = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) ?: ('Cuenta ' . $person->id);
            }
        }
        // Validación extra: solo una cuenta de tipo 'treasury' permitida
        if ($data['type'] === 'treasury') {
            $existing = Account::where('type', 'treasury')->first();
            if ($existing) {
                return back()->withErrors(['type' => 'Ya existe una cuenta Tesorería en el sistema.'])->withInput();
            }
        }
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
        $this->authorize('view', $account);

        return response()->json($account->load('person'));
    }

    public function movements(Account $account, \App\Services\AccountStatementService $statements)
    {
        $account->load('person');

        return view('accounts.movements', ['account' => $account, ...$statements->build($account)]);
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
            'balance' => 'nullable|numeric',
            'balance_adjustment_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_enabled' => 'in:0,1',
        ]);

        $data = $request->only(['name', 'type', 'person_id', 'notes']);

        // El saldo solo cambia por transacciones; un ajuste manual exige rol boss y motivo
        $balanceAdjustment = null;
        if ($request->filled('balance') && round((float) $request->input('balance'), 2) !== round((float) $account->balance, 2)) {
            if (! $request->user()->hasRole('boss')) {
                return back()->withErrors(['balance' => 'Solo el jefe puede ajustar saldos manualmente.'])->withInput();
            }
            if (! $request->filled('balance_adjustment_reason')) {
                return back()->withErrors(['balance_adjustment_reason' => 'Indica el motivo del ajuste de saldo.'])->withInput();
            }
            $data['balance'] = $request->input('balance');
            $balanceAdjustment = [
                'old' => (float) $account->balance,
                'new' => (float) $request->input('balance'),
                'reason' => $request->input('balance_adjustment_reason'),
            ];
        }

        // Validación extra: evitar convertir otra cuenta en 'treasury' si ya existe una diferente
        if ($data['type'] === 'treasury') {
            $existing = Account::where('type', 'treasury')->where('id', '<>', $account->id)->first();
            if ($existing) {
                return back()->withErrors(['type' => 'Ya existe otra cuenta Tesorería en el sistema.'])->withInput();
            }
        }
        $data['is_enabled'] = $request->input('is_enabled', 0);

        // Validate against DB max to avoid numeric overflow
        if (isset($data['balance']) && $data['balance'] > Account::MAX_BALANCE) {
            return back()->withErrors(['balance' => 'El saldo es demasiado grande. Valor máximo permitido: ' . number_format(Account::MAX_BALANCE, 2, ',', '.')])->withInput();
        }

        try {
            $account->update($data);

            if ($balanceAdjustment) {
                activity('balance_adjustment')
                    ->performedOn($account)
                    ->causedBy($request->user())
                    ->withProperties($balanceAdjustment)
                    ->log('Ajuste manual de saldo');
            }
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'numeric field overflow') || str_contains($e->getMessage(), 'numeric value out of range')) {
                return back()->withErrors(['balance' => 'El valor del saldo excede el máximo permitido por la base de datos.'])->withInput();
            }
            throw $e;
        }

        return redirect()->route('accounts.index')->with('success', 'Cuenta actualizada exitosamente');
    }

    public function destroy(Account $account)
    {
        // Verificar si la cuenta tiene transacciones asociadas (desde o hacia esta cuenta)
        $transactionCount = $account->transactionsFrom()->count() + $account->transactionsTo()->count();
        
        // Prevent deletion of protected or special accounts
        if (!empty($account->is_protected)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta cuenta está protegida y no se puede eliminar.'
            ], 400);
        }

        if ($account->type === 'treasury') {
            return response()->json([
                'success' => false,
                'message' => 'La cuenta de tesorería no se puede eliminar.'
            ], 400);
        }

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
