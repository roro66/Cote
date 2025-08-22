<?php

namespace App\Http\Controllers\DataTables;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class TeamDataTableController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Team::with(['leader'])->select('teams.*');
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('leader_name', function($row) {
                    return $row->leader ? $row->leader->full_name : 'Sin líder';
                })
                ->addColumn('leader_rut', function($row) {
                    return $row->leader ? $row->leader->rut_formatted : '-';
                })
                ->addColumn('action', function($row) {
                    $actionBtn = '<div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary btn-sm" onclick="editTeam('.$row->id.')">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteTeam('.$row->id.')">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>';
                    return $actionBtn;
                })
                ->addColumn('status', function($row) {
                    $status = $row->is_enabled ? 'Activo' : 'Inactivo';
                    $class = $row->is_enabled ? 'bg-success' : 'bg-danger';
                    return '<span class="badge '.$class.'">'.$status.'</span>';
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }
        
        return response()->json(['error' => 'Not an AJAX request'], 400);
    }
}
