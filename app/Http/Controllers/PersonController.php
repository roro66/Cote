<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Bank;
use App\Models\AccountType;
use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Http\Resources\PersonResource;
use App\Services\PersonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonController extends Controller
{
    protected PersonService $personService;

    public function __construct(PersonService $personService)
    {
        $this->personService = $personService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $stats = $this->personService->getStats();

        if ($request->ajax()) {
            return response()->json([
                'statistics' => $stats
            ]);
        }

        return view('people.index', [
            'stats' => $stats,
            'banks' => Bank::active()->orderBy('name')->get(),
            'accountTypes' => AccountType::active()->orderBy('name')->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('people.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePersonRequest $request)
    {
        try {
            $person = $this->personService->create($request->validated());

            // Si es petición AJAX, devolver JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Persona creada exitosamente',
                    'data' => new PersonResource($person)
                ]);
            }

            // Si es petición normal, redirigir con mensaje de éxito
            return redirect()->route('people.index')->with('success', 'Persona creada exitosamente');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la persona: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Error al crear la persona: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Person $person): JsonResponse
    {
        return response()->json(new PersonResource($person));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Person $person): View
    {
        return view('people.edit', compact('person'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePersonRequest $request, Person $person)
    {
        try {
            $updatedPerson = $this->personService->update($person, $request->validated());

            // Si es petición AJAX, devolver JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Persona actualizada exitosamente',
                    'data' => new PersonResource($updatedPerson)
                ]);
            }

            // Si es petición normal, redirigir con mensaje de éxito
            return redirect()->route('people.index')->with('success', 'Persona actualizada exitosamente');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar la persona: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Error al actualizar la persona: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Person $person): JsonResponse
    {
        try {
            $result = $this->personService->delete($person);

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la persona: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export all people for DataTables
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search');
            $people = $this->personService->getAllForExport($search);
            $data = $this->personService->formatForExport($people);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al exportar los datos: ' . $e->getMessage()
            ], 500);
        }
    }
}
