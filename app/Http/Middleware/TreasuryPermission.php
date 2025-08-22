<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TreasuryPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        // Verificar si el usuario está autenticado
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Verificar si el usuario está habilitado
        if (!$user->is_enabled) {
            abort(403, 'Tu cuenta está deshabilitada. Contacta al administrador.');
        }

        // Si se especifica un permiso, verificarlo
        if ($permission) {
            // Aquí puedes implementar lógica más compleja con roles/permisos
            switch ($permission) {
                case 'treasury':
                    // Solo administradores pueden manejar tesorería
                    if (!$user->hasRole('admin') && !$user->can('manage-treasury')) {
                        abort(403, 'No tienes permisos para acceder a la gestión de tesorería.');
                    }
                    break;
                case 'approve-transactions':
                    // Solo supervisores y admin pueden aprobar transacciones
                    if (!$user->hasRole(['admin', 'supervisor']) && !$user->can('approve-transactions')) {
                        abort(403, 'No tienes permisos para aprobar transacciones.');
                    }
                    break;
                case 'view-reports':
                    // Cualquier usuario autenticado puede ver reportes básicos
                    break;
            }
        }

        return $next($request);
    }
}
