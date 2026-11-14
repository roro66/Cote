<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Services\PersonService;
use Illuminate\Support\Str;

/**
 * Cada trabajador con su dinero y las acciones de todos los días, en una sola pantalla.
 */
class WorkerController extends Controller
{
    public function index()
    {
        $people = Person::with(['accounts' => fn ($q) => $q->where('type', 'person')->orderByDesc('is_enabled')])
            ->where('is_enabled', true)
            ->get()
            ->sortBy(fn (Person $p) => Str::lower(Str::ascii($p->full_name)))
            ->values();

        $accounts = $people->map(fn (Person $p) => $p->accounts->first())->filter();

        return view('workers.index', [
            'people' => $people,
            'holding' => (float) $accounts->where('balance', '>', 0)->sum('balance'),
            'owed' => abs((float) $accounts->where('balance', '<', 0)->sum('balance')),
        ]);
    }

    public function openAccount(Person $person, PersonService $service)
    {
        $service->openAccount($person);

        return redirect()->route('workers.index')
            ->with('toastr', ['type' => 'success', 'message' => "Listo, {$person->first_name} ya tiene su cuenta. Ahora puedes entregarle dinero."]);
    }
}
