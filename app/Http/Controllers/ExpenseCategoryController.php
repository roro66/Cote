<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        return view('expense_categories.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'nullable|string|max:20|unique:expense_categories,code',
            'name' => 'required|string|max:255|unique:expense_categories,name',
            'description' => 'nullable|string|max:1000',
        ]);

        ExpenseCategory::create($request->only(['code', 'name', 'description', 'is_enabled']));

        return response()->json(['message' => 'Categoría creada exitosamente']);
    }

    public function show(ExpenseCategory $expenseCategory)
    {
        return response()->json($expenseCategory);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $request->validate([
            'code' => 'nullable|string|max:20|unique:expense_categories,code,' . $expenseCategory->id,
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $expenseCategory->id,
            'description' => 'nullable|string|max:1000',
        ]);

        $expenseCategory->update($request->only(['code', 'name', 'description', 'is_enabled']));

        return response()->json(['message' => 'Categoría actualizada exitosamente']);
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        // Prevent deleting if referenced by items
        $count = $expenseCategory->items()->count() ?? 0;
        if ($count > 0) {
            return response()->json(['message' => 'No se puede eliminar la categoría porque tiene items asociados'], 400);
        }

        $expenseCategory->delete();

        return response()->json(['message' => 'Categoría eliminada exitosamente']);
    }
}
