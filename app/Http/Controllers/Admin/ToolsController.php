<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ToolsController extends Controller
{
    public function index()
    {
        return view('admin.tools');
    }

    public function normalizeLegacyTransactions(Request $request)
    {
        $dryRun = (bool) $request->boolean('dry_run', true);
        $params = $dryRun ? ['--dry-run' => true] : [];

        // Capturar salida del comando
        Artisan::call('coteso:normalize-legacy-transactions', $params);
        $output = Artisan::output();

        return redirect()->route('admin.tools')
            ->with('status', $dryRun ? 'Dry-run ejecutado' : 'Normalización ejecutada')
            ->with('output', $output);
    }
}
