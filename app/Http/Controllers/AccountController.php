<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Person;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        // Estadísticas para el dashboard de cuentas
        $total = \App\Models\Account::count();
        $enabled = \App\Models\Account::where('is_enabled', true)->count();
        $treasury = \App\Models\Account::where('type', 'treasury')->count();
        $personal = \App\Models\Account::where('type', 'person')->count();
        // Exclude treasury and fondeo accounts from the totals shown to represent money
        // that is effectively held by people (not the organization's treasury/funding pool).
        // Some rows may have is_fondeo NULL; treat NULL as false and exclude only where is_fondeo is true
        $nonzero = \App\Models\Account::where('balance', '<>', 0)
            ->where('type', '<>', 'treasury')
            ->where(function ($q) {
                $q->where('is_fondeo', false)->orWhereNull('is_fondeo');
            })
            ->count();

        // Sum of all accounts (including treasury and fondeo)
        $total_balance_all = \App\Models\Account::sum('balance');

                // Sum of treasury + fondeo accounts (special pool).
                // Some existing rows may not have `is_fondeo=true` set, so also treat any
                // account whose name mentions 'fondeo' (case-insensitive) as fondeo.
                $total_balance_special = \App\Models\Account::where(function ($q) {
                        $q->where('type', 'treasury')
                            ->orWhere('is_fondeo', true)
                            ->orWhereRaw("name ILIKE ?", ['%fondeo%']);
                })->sum('balance');

        // Sum excluding treasury and fondeo (money in possession of people).
        // Exclude accounts that are treasury OR that are explicitly marked as fondeo
        // or whose name contains 'fondeo'. Treat NULL is_fondeo as false.
        $total_balance = \App\Models\Account::where('type', '<>', 'treasury')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('is_fondeo', false)->orWhereNull('is_fondeo');
                })->whereRaw("name NOT ILIKE ?", ['%fondeo%']);
            })
            ->sum('balance');

        $stats = [
            'total' => $total,
            'enabled' => $enabled,
            'treasury' => $treasury,
            'personal' => $personal,
            'nonzero' => $nonzero,
            'total_balance' => $total_balance,
            'total_balance_all' => $total_balance_all,
            'total_balance_special' => $total_balance_special,
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
            'balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'is_enabled' => 'in:0,1',
            'is_fondeo' => 'in:0,1',
        ]);

        $data = $request->only(['name', 'type', 'person_id', 'balance', 'notes', 'is_fondeo']);
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
        // Validación extra: solo una cuenta con is_fondeo = true
        if ($request->input('is_fondeo')) {
            $existingFondeo = Account::where('is_fondeo', true)->first();
            if ($existingFondeo) {
                return back()->withErrors(['is_fondeo' => 'Ya existe una cuenta marcada como Fondeo en el sistema.'])->withInput();
            }
            $data['is_fondeo'] = 1;
        } else {
            $data['is_fondeo'] = 0;
        }

        // Validate against DB max to avoid numeric overflow (decimal(15,2) -> max ~ 9,999,999,999,999.99)
        if (isset($data['balance']) && $data['balance'] > Account::MAX_BALANCE) {
            return back()->withErrors(['balance' => 'El saldo es demasiado grande. Valor máximo permitido: ' . number_format(Account::MAX_BALANCE, 2, ',', '.')])->withInput();
        }

        try {
            $account = Account::create($data);
        } catch (\Illuminate\Database\QueryException $e) {
            // Detect numeric overflow and return a friendly message
            if (str_contains($e->getMessage(), 'numeric field overflow') || str_contains($e->getMessage(), 'numeric value out of range')) {
                return back()->withErrors(['balance' => 'El valor del saldo excede el máximo permitido por la base de datos.'])->withInput();
            }
            throw $e;
        }

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
            'is_fondeo' => 'in:0,1',
        ]);

        $data = $request->only(['name', 'type', 'person_id', 'balance', 'notes', 'is_fondeo']);
        // Validación extra: evitar convertir otra cuenta en 'treasury' si ya existe una diferente
        if ($data['type'] === 'treasury') {
            $existing = Account::where('type', 'treasury')->where('id', '<>', $account->id)->first();
            if ($existing) {
                return back()->withErrors(['type' => 'Ya existe otra cuenta Tesorería en el sistema.'])->withInput();
            }
        }
        $data['is_enabled'] = $request->input('is_enabled', 0);
        // Validación extra: evitar marcar otra cuenta como is_fondeo
        if ($request->input('is_fondeo')) {
            $existingFondeo = Account::where('is_fondeo', true)->where('id', '<>', $account->id)->first();
            if ($existingFondeo) {
                return back()->withErrors(['is_fondeo' => 'Ya existe otra cuenta marcada como Fondeo en el sistema.'])->withInput();
            }
            $data['is_fondeo'] = 1;
        } else {
            // Si no viene marcado, aseguramos el valor a 0
            $data['is_fondeo'] = 0;
        }

        // Validate against DB max to avoid numeric overflow
        if (isset($data['balance']) && $data['balance'] > Account::MAX_BALANCE) {
            return back()->withErrors(['balance' => 'El saldo es demasiado grande. Valor máximo permitido: ' . number_format(Account::MAX_BALANCE, 2, ',', '.')])->withInput();
        }

        try {
            $account->update($data);
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

        if (!empty($account->is_fondeo)) {
            return response()->json([
                'success' => false,
                'message' => 'La cuenta de fondeo no se puede eliminar.'
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
