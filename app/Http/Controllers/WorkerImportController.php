<?php

namespace App\Http\Controllers;

use App\Services\ExcelExportService;
use App\Services\PeopleImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Importar la nómina de trabajadores desde Excel: subir, revisar fila por fila e importar.
 * El archivo subido se guarda solo mientras se revisa y se borra al importar.
 */
class WorkerImportController extends Controller
{
    private const SESSION_KEY = 'worker_import_file';

    public function __construct(private PeopleImportService $importer) {}

    public function show(Request $request)
    {
        $this->forgetFile($request);

        return view('workers.import', ['analysis' => null]);
    }

    public function template(ExcelExportService $excel)
    {
        return $excel->exportPeopleTemplate('planilla_trabajadores.xlsx');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|max:5120|mimes:xlsx,csv,txt,ods',
        ], [
            'archivo.required' => 'Elige el archivo Excel con los trabajadores.',
            'archivo.mimes' => 'El archivo debe ser Excel (.xlsx), CSV u OpenOffice (.ods). Si es un Excel antiguo (.xls), ábrelo y guárdalo como .xlsx.',
            'archivo.max' => 'El archivo pesa demasiado (máximo 5 MB).',
        ]);

        $this->forgetFile($request);
        $file = $request->file('archivo');
        $extension = Str::lower($file->getClientOriginalExtension() ?: $file->extension());
        $path = $file->storeAs('imports', Str::uuid() . '.' . $extension, 'local');
        $request->session()->put(self::SESSION_KEY, $path);

        try {
            $analysis = $this->importer->analyze(Storage::disk('local')->path($path), $extension);
        } catch (\Throwable $e) {
            report($e);
            $this->forgetFile($request);

            return back()->withErrors(['archivo' => 'No pude leer el archivo. Revisa que sea una planilla de Excel y que no esté protegida con contraseña.']);
        }

        return view('workers.import', ['analysis' => $analysis, 'fileName' => $file->getClientOriginalName()]);
    }

    public function store(Request $request)
    {
        $path = $request->session()->get(self::SESSION_KEY);
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return redirect()->route('workers.import')->withErrors(['archivo' => 'Vuelve a subir el archivo para revisarlo.']);
        }

        $analysis = $this->importer->analyze(Storage::disk('local')->path($path), pathinfo($path, PATHINFO_EXTENSION));
        $result = $this->importer->import($analysis, $request->user());
        $this->forgetFile($request);

        $parts = array_filter([
            $result['created'] ? "{$result['created']} trabajadores nuevos" : null,
            $result['updated'] ? "{$result['updated']} actualizados" : null,
            $result['bank_accounts'] ? "{$result['bank_accounts']} cuentas bancarias" : null,
            $result['balances'] ? "{$result['balances']} saldos iniciales" : null,
        ]);

        return redirect()->route('workers.index')->with('toastr', [
            'type' => 'success',
            'message' => $parts ? 'Importación lista: ' . implode(', ', $parts) . '.' : 'No había filas para importar.',
        ]);
    }

    private function forgetFile(Request $request): void
    {
        if ($old = $request->session()->pull(self::SESSION_KEY)) {
            Storage::disk('local')->delete($old);
        }
    }
}
