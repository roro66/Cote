<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use Illuminate\Http\Request;

class AccountTypeController extends Controller
{
    public function index()
    {
        return view('account-types.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:account_types',
        ]);

        AccountType::create($request->only(['name']));

        return response()->json(['message' => 'Tipo de cuenta creado exitosamente']);
    }

    public function show(AccountType $accountType)
    {
        return response()->json($accountType);
    }

    public function update(Request $request, AccountType $accountType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:account_types,name,' . $accountType->id,
        ]);

        $accountType->update($request->only(['name']));

        return response()->json(['message' => 'Tipo de cuenta actualizado exitosamente']);
    }

    public function destroy(AccountType $accountType)
    {
        // Verificar si el tipo de cuenta tiene personas asociadas
        if ($accountType->people()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el tipo de cuenta porque tiene personas asociadas'
            ], 400);
        }

        $accountType->delete();

        return response()->json(['message' => 'Tipo de cuenta eliminado exitosamente']);
    }
}
