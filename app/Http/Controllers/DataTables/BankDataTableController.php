<?php

namespace App\Http\Controllers\DataTables;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class BankDataTableController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Bank::withCount('people');
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row) {
                    $btn = '<button class="btn btn-sm btn-primary me-1" onclick="editBank(' . $row->id . ')" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>';
                    $btn .= '<button class="btn btn-sm btn-danger" onclick="deleteBank(' . $row->id . ', \'' . addslashes($row->name) . '\')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        
        return response()->json(['error' => 'Invalid request'], 400);
    }
}
