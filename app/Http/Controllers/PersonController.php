<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Rules\ValidChileanRut;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PersonController extends Controller
{
    /**
     * Mostrar la lista de personas
     */
    public function index(): View
    {
        return view('people.index');
    }

    /**
     * Guardar una nueva persona
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'rut' => ['required', 'string', 'unique:people,rut', new ValidChileanRut()],
                'email' => 'required|email|unique:people,email',
                'phone' => 'nullable|string|max:20',
                'role_type' => 'required|in:tesorero,trabajador',
                'bank_name' => 'nullable|string|max:255',
                'account_type' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:500',
            ]);

            // Manejar is_enabled por separado ya que los checkboxes no envían valor cuando no están marcados
            $validated['is_enabled'] = $request->has('is_enabled');

            $person = Person::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Persona creada exitosamente',
                'data' => $person
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la persona: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar una persona específica
     */
    public function show(Person $person): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $person
        ]);
    }

    /**
     * Actualizar una persona
     */
    public function update(Request $request, Person $person): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'rut' => ['required', 'string', 'unique:people,rut,' . $person->id, new ValidChileanRut()],
                'email' => 'required|email|unique:people,email,' . $person->id,
                'phone' => 'nullable|string|max:20',
                'role_type' => 'required|in:tesorero,trabajador',
                'bank_name' => 'nullable|string|max:255',
                'account_type' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:500',
            ]);

            // Manejar is_enabled por separado ya que los checkboxes no envían valor cuando no están marcados
            $validated['is_enabled'] = $request->has('is_enabled');

            $person->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Persona actualizada exitosamente',
                'data' => $person
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la persona: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una persona
     */
    public function destroy(Person $person): JsonResponse
    {
        try {
            // Verificar si tiene relaciones que impedirían la eliminación
            $hasAccounts = $person->accounts()->count() > 0;
            $hasLedTeams = $person->ledTeams()->count() > 0;
            $hasSubmittedExpenses = $person->submittedExpenses()->count() > 0;
            $hasUser = $person->user()->exists();
            
            if ($hasAccounts || $hasLedTeams || $hasSubmittedExpenses || $hasUser) {
                $dependencies = [];
                if ($hasAccounts) $dependencies[] = 'cuentas';
                if ($hasLedTeams) $dependencies[] = 'equipos como líder';
                if ($hasSubmittedExpenses) $dependencies[] = 'gastos';
                if ($hasUser) $dependencies[] = 'usuario del sistema';
                
                $message = 'No se puede eliminar esta persona porque tiene ' . implode(', ', $dependencies) . ' asociados.';
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            }
            
            $person->delete();

            return response()->json([
                'success' => true,
                'message' => 'Persona eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la persona: ' . $e->getMessage()
            ], 500);
        }
    }
}
