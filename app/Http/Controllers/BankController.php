<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function index()
    {
        return view('banks.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:banks',
            'name' => 'required|string|max:255|unique:banks',
        ]);

        Bank::create($request->only(['code', 'name']));

        return response()->json(['message' => 'Banco creado exitosamente']);
    }

    public function show(Bank $bank)
    {
        return response()->json($bank);
    }

    public function update(Request $request, Bank $bank)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:banks,code,' . $bank->id,
            'name' => 'required|string|max:255|unique:banks,name,' . $bank->id,
        ]);

        $bank->update($request->only(['code', 'name']));

        return response()->json(['message' => 'Banco actualizado exitosamente']);
    }

    public function destroy(Bank $bank)
    {
        // Verificar si el banco tiene personas asociadas
        if ($bank->people()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el banco porque tiene personas asociadas'
            ], 400);
        }

        $bank->delete();

        return response()->json(['message' => 'Banco eliminado exitosamente']);
    }
}
