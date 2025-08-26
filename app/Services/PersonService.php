<?php

namespace App\Services;

use App\Models\Person;
use App\Http\Resources\PersonResource;
use App\Helpers\RutHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class PersonService
{
    /**
     * Create a new person
     */
    public function create(array $data): Person
    {
        // Handle is_enabled checkbox - convert 'on', '1', 'true' to true; anything else to false
        $data['is_enabled'] = $this->normalizeBoolean($data['is_enabled'] ?? false);

        return Person::create($data);
    }

    /**
     * Update an existing person
     */
    public function update(Person $person, array $data): Person
    {
        // Handle is_enabled checkbox - convert 'on', '1', 'true' to true; anything else to false
        $data['is_enabled'] = $this->normalizeBoolean($data['is_enabled'] ?? false);

        $person->update($data);

        return $person->fresh();
    }

    /**
     * Normalize boolean values from form inputs
     */
    private function normalizeBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes']);
        }

        return (bool) $value;
    }

    /**
     * Delete a person with dependency checks
     */
    public function delete(Person $person): array
    {
        // Check for dependencies that prevent deletion
        $dependencies = $this->checkDependencies($person);

        if (!empty($dependencies)) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar esta persona porque tiene ' .
                    implode(', ', $dependencies) . ' asociados.'
            ];
        }

        $person->delete();

        return [
            'success' => true,
            'message' => 'Persona eliminada exitosamente'
        ];
    }

    /**
     * Check dependencies that prevent deletion
     */
    private function checkDependencies(Person $person): array
    {
        $dependencies = [];

        if ($person->accounts()->count() > 0) {
            $dependencies[] = 'cuentas';
        }

        if ($person->ledTeams()->count() > 0) {
            $dependencies[] = 'equipos como líder';
        }

        if ($person->submittedExpenses()->count() > 0) {
            $dependencies[] = 'gastos';
        }

        if ($person->user()->exists()) {
            $dependencies[] = 'usuario del sistema';
        }

        return $dependencies;
    }

    /**
     * Get all people for export
     */
    public function getAllForExport(?string $search = null): Collection
    {
        $query = Person::with(['bank', 'accountType'])
            ->select([
                'id',
                'first_name',
                'last_name',
                'rut',
                'email',
                'phone',
                'bank_id',
                'account_type_id',
                'account_number',
                'is_enabled'
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('rut', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name,' ',last_name) like ?", ["%{$search}%"])
                    ->orWhereHas('bank', function ($bankQuery) use ($search) {
                        $bankQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('accountType', function ($typeQuery) use ($search) {
                        $typeQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('last_name')->orderBy('first_name')->get();
    }

    /**
     * Format export data
     */
    public function formatForExport(Collection $people): array
    {
        $data = [];

        foreach ($people as $index => $person) {
            $data[] = [
                'DT_RowIndex' => $index + 1,
                'full_name' => $person->first_name . ' ' . $person->last_name,
                'rut' => RutHelper::format($person->rut),
                'email' => $person->email,
                'phone' => $person->phone ?? 'N/A',
                'bank_name' => $person->bank?->name ?? 'Sin banco',
                'account_type_name' => $person->accountType?->name ?? 'Sin tipo',
                'account_number' => $person->account_number ?? '—',
                'status' => $person->is_enabled ? 'Activo' : 'Inactivo',
            ];
        }

        return $data;
    }

    /**
     * Get person statistics
     */
    public function getStats(): array
    {
        $total = Person::count();
        $active = Person::where('is_enabled', true)->count();
        $inactive = $total - $active;
        $tesoreros = Person::where('role_type', 'tesorero')->count();
        $trabajadores = Person::where('role_type', 'trabajador')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'tesoreros' => $tesoreros,
            'trabajadores' => $trabajadores,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
        ];
    }
}
