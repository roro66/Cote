<?php

namespace App\Livewire;

use App\Models\AccountType;
use Livewire\Component;
use Livewire\WithPagination;

class AccountTypeList extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Form fields
    public $accountTypeId = null;
    public $name = '';
    public $description = '';
    public $is_active = true;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    protected $rules = [
        'name' => 'required|string|max:255|unique:account_types,name',
        'description' => 'nullable|string|max:500',
        'is_active' => 'boolean',
    ];

    protected $messages = [
        'name.required' => 'El nombre es obligatorio.',
        'name.max' => 'El nombre no puede tener más de 255 caracteres.',
        'name.unique' => 'Este nombre ya está en uso.',
        'description.max' => 'La descripción no puede tener más de 500 caracteres.',
    ];

    protected $listeners = ['refreshList' => '$refresh'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function edit($id)
    {
        $accountType = AccountType::findOrFail($id);
        $this->accountTypeId = $accountType->id;
        $this->name = $accountType->name;
        $this->description = $accountType->description;
        $this->is_active = $accountType->is_active;
    }

    public function save()
    {
        $rules = $this->rules;
        if ($this->accountTypeId) {
            $rules['name'] = 'required|string|max:255|unique:account_types,name,' . $this->accountTypeId;
        }

        $this->validate($rules);

        try {
            if ($this->accountTypeId) {
                // Update existing account type
                $accountType = AccountType::findOrFail($this->accountTypeId);
                $accountType->update([
                    'name' => $this->name,
                    'description' => $this->description,
                    'is_active' => $this->is_active,
                ]);
                $message = 'Tipo de cuenta actualizado exitosamente.';
            } else {
                // Create new account type
                AccountType::create([
                    'name' => $this->name,
                    'description' => $this->description,
                    'is_active' => $this->is_active,
                ]);
                $message = 'Tipo de cuenta creado exitosamente.';
            }

            $this->resetForm();
            $this->dispatch('showToastr', type: 'success', message: $message);
        } catch (\Exception $e) {
            $this->dispatch('showToastr', type: 'error', message: 'Error al guardar el tipo de cuenta: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $accountType = AccountType::findOrFail($id);
            
            // Verificar si tiene personas asociadas
            if ($accountType->people()->count() > 0) {
                $this->dispatch('showToastr', type: 'error', message: 'No se puede eliminar este tipo de cuenta porque tiene personas asociadas.');
                return;
            }

            $accountType->delete();
            $this->dispatch('showToastr', type: 'success', message: 'Tipo de cuenta eliminado exitosamente.');
        } catch (\Exception $e) {
            $this->dispatch('showToastr', type: 'error', message: 'Error al eliminar el tipo de cuenta: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->accountTypeId = null;
        $this->name = '';
        $this->description = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $accountTypes = AccountType::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.account-type-list', compact('accountTypes'));
    }
}
