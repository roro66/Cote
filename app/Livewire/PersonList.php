<?php

namespace App\Livewire;

use App\Models\Person;
use App\Rules\ValidChileanRut;
use App\Helpers\RutHelper;
use Livewire\Component;
use Livewire\WithPagination;

class PersonList extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'first_name';
    public $sortDirection = 'asc';
    public $showingForm = false;
    public $editing = false;
    public $personId = null;

    // Form fields
    public $first_name = '';
    public $last_name = '';
    public $rut = '';
    public $email = '';
    public $phone = '';
    public $role_type = 'trabajador';
    public $is_enabled = true;

    protected function rules()
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'role_type' => 'required|in:tesorero,trabajador',
            'is_enabled' => 'boolean',
        ];

        if ($this->editing && $this->personId) {
            $rules['rut'] = [
                'required',
                'string',
                'unique:people,rut,' . $this->personId . ',id',
                new ValidChileanRut()
            ];
            $rules['email'] = [
                'required',
                'email',
                'unique:people,email,' . $this->personId . ',id'
            ];
        } else {
            $rules['rut'] = [
                'required',
                'string',
                'unique:people,rut',
                new ValidChileanRut()
            ];
            $rules['email'] = [
                'required',
                'email',
                'unique:people,email'
            ];
        }

        return $rules;
    }

    // Hook para limpiar RUT cuando se actualiza
    public function updatedRut($value)
    {
        $this->rut = RutHelper::clean($value);
    }

    protected $messages = [
        'first_name.required' => 'El nombre es obligatorio.',
        'first_name.string' => 'El nombre debe ser texto.',
        'first_name.max' => 'El nombre no puede tener más de :max caracteres.',
        'last_name.required' => 'El apellido es obligatorio.',
        'last_name.string' => 'El apellido debe ser texto.',
        'last_name.max' => 'El apellido no puede tener más de :max caracteres.',
        'rut.required' => 'El RUT es obligatorio.',
        'rut.string' => 'El RUT debe ser texto.',
        'rut.unique' => 'Este RUT ya está registrado.',
        'email.required' => 'El correo electrónico es obligatorio.',
        'email.email' => 'El correo electrónico debe tener un formato válido.',
        'email.unique' => 'Este correo electrónico ya está registrado.',
        'phone.string' => 'El teléfono debe ser texto.',
        'phone.max' => 'El teléfono no puede tener más de :max caracteres.',
        'role_type.required' => 'El rol es obligatorio.',
        'role_type.in' => 'El rol seleccionado no es válido.',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    public function create()
    {
        $this->resetForm();
        $this->showingForm = true;
        $this->editing = false;
    }

    public function edit($id)
    {
        $person = Person::findOrFail($id);
        $this->personId = $person->id;
        $this->first_name = $person->first_name;
        $this->last_name = $person->last_name;
        $this->rut = $person->rut;
        $this->email = $person->email;
        $this->phone = $person->phone;
        $this->role_type = $person->role_type;
        $this->is_enabled = $person->is_enabled;
        $this->showingForm = true;
        $this->editing = true;
    }

    public function save()
    {
        // Clean RUT before validation
        $this->rut = RutHelper::clean($this->rut);
        
        $this->validate();

        if ($this->editing) {
            $person = Person::findOrFail($this->personId);
            $person->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'rut' => $this->rut,
                'email' => $this->email,
                'phone' => $this->phone,
                'role_type' => $this->role_type,
                'is_enabled' => $this->is_enabled,
            ]);
            
            $this->dispatch('showToastr', type: 'success', message: 'Persona actualizada exitosamente');
        } else {
            Person::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'rut' => $this->rut,
                'email' => $this->email,
                'phone' => $this->phone,
                'role_type' => $this->role_type,
                'is_enabled' => $this->is_enabled,
            ]);
            
            $this->dispatch('showToastr', type: 'success', message: 'Persona creada exitosamente');
        }

        $this->resetForm();
        $this->showingForm = false;
    }

    public function delete($id)
    {
        try {
            $person = Person::findOrFail($id);
            
            // Verificar si tiene relaciones que impedirían la eliminación
            $hasAccounts = $person->accounts()->count() > 0;
            $hasLedTeams = $person->ledTeams()->count() > 0;
            $hasSubmittedExpenses = $person->submittedExpenses()->count() > 0;
            $hasUser = $person->user()->exists();
            
            if ($hasAccounts || $hasLedTeams || $hasSubmittedExpenses || $hasUser) {
                $dependencies = [];
                if ($hasAccounts) $dependencies[] = 'cuentas';
                if ($hasLedTeams) $dependencies[] = 'equipos como líder';
                if ($hasSubmittedExpenses) $dependencies[] = 'gastos';
                if ($hasUser) $dependencies[] = 'usuario del sistema';
                
                $message = 'No se puede eliminar esta persona porque tiene ' . implode(', ', $dependencies) . ' asociados.';
                $this->dispatch('showToastr', type: 'error', message: $message);
                return;
            }
            
            $person->delete();
            $this->dispatch('showToastr', type: 'success', message: 'Persona eliminada exitosamente');
            
        } catch (\Exception $e) {
            $this->dispatch('showToastr', type: 'error', message: 'Error al eliminar la persona: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        $this->resetForm();
        $this->showingForm = false;
    }

    private function resetForm()
    {
        $this->first_name = '';
        $this->last_name = '';
        $this->rut = '';
        $this->email = '';
        $this->phone = '';
        $this->role_type = 'trabajador';
        $this->is_enabled = true;
        $this->personId = null;
        $this->editing = false;
    }

    public function render()
    {
        $people = Person::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%')
                      ->orWhere('rut', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.person-list', compact('people'));
    }
}
