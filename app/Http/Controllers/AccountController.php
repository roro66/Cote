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
        $nonzero = \App\Models\Account::where('balance', '<>', 0)->count();
        $total_balance = \App\Models\Account::sum('balance');

        $stats = [
            'total' => $total,
            'enabled' => $enabled,
            'treasury' => $treasury,
            'personal' => $personal,
            'nonzero' => $nonzero,
            'total_balance' => $total_balance,
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
