<?php

namespace App\Http\Controllers\DataTables;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class PersonDataTableController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Person::select('*');
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('full_name', function($row) {
                    return $row->first_name . ' ' . $row->last_name;
                })
                ->addColumn('status', function($row) {
                    $status = $row->is_enabled ? 'Activo' : 'Inactivo';
                    $class = $row->is_enabled ? 'bg-success' : 'bg-danger';
                    return '<span class="badge '.$class.'">'.$status.'</span>';
                })
                ->addColumn('action', function($row) {
                    $actionBtn = '<div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary btn-sm edit-btn" onclick="editPerson('.$row->id.')">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deletePerson('.$row->id.')">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>';
                    return $actionBtn;
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }
        
        return response()->json(['error' => 'Not an AJAX request'], 400);
    }
}
