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
                ->editColumn('rut', function($row) {
                    // Formatear RUT con puntos si no los tiene
                    if (strpos($row->rut, '.') === false && strlen($row->rut) >= 8) {
                        $rut = str_replace('-', '', $row->rut);
                        $dv = substr($rut, -1);
                        $number = substr($rut, 0, -1);
                        $formatted = number_format($number, 0, '', '.') . '-' . $dv;
                        return $formatted;
                    }
                    return $row->rut;
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
                ->filterColumn('full_name', function($query, $keyword) {
                    $query->whereRaw("CONCAT(first_name,' ',last_name) like ?", ["%{$keyword}%"]);
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $searchValue = $request->search['value'];
                        $query->where(function($q) use ($searchValue) {
                            $q->where('first_name', 'like', "%{$searchValue}%")
                              ->orWhere('last_name', 'like', "%{$searchValue}%")
                              ->orWhere('rut', 'like', "%{$searchValue}%")
                              ->orWhere('email', 'like', "%{$searchValue}%")
                              ->orWhere('phone', 'like', "%{$searchValue}%")
                              ->orWhereRaw("CONCAT(first_name,' ',last_name) like ?", ["%{$searchValue}%"]);
                        });
                    }
                })
                ->rawColumns(['action', 'status', 'rut'])
                ->make(true);
        }
        
        return response()->json(['error' => 'Not an AJAX request'], 400);
    }
}
