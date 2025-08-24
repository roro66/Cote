<?php

namespace App\Livewire;

use App\Models\Bank;
use Livewire\Component;
use Livewire\WithPagination;

class BankList extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 10;
    public $typeFilter = '';

    // Form fields
    public $bankId = null;
    public $name = '';
    public $code = '';
    public $type = 'banco';
    public $is_active = true;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'typeFilter' => ['except' => ''],
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:10|unique:banks,code',
        'type' => 'required|in:banco,cooperativa,tarjeta_prepago',
        'is_active' => 'boolean',
    ];

    protected $messages = [
        'name.required' => 'El nombre es obligatorio.',
        'name.max' => 'El nombre no puede tener más de 255 caracteres.',
        'code.required' => 'El código es obligatorio.',
        'code.max' => 'El código no puede tener más de 10 caracteres.',
        'code.unique' => 'Este código ya está en uso.',
        'type.required' => 'El tipo es obligatorio.',
        'type.in' => 'El tipo debe ser banco, cooperativa o tarjeta prepago.',
    ];

    protected $listeners = ['refreshList' => '$refresh'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
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
        $bank = Bank::findOrFail($id);
        $this->bankId = $bank->id;
        $this->name = $bank->name;
        $this->code = $bank->code;
        $this->type = $bank->type;
        $this->is_active = $bank->is_active;
    }

    public function save()
    {
        $rules = $this->rules;
        if ($this->bankId) {
            $rules['code'] = 'required|string|max:10|unique:banks,code,' . $this->bankId;
        }

        $this->validate($rules);

        try {
            if ($this->bankId) {
                // Update existing bank
                $bank = Bank::findOrFail($this->bankId);
                $bank->update([
                    'name' => $this->name,
                    'code' => $this->code,
                    'type' => $this->type,
                    'is_active' => $this->is_active,
                ]);
                $message = 'Banco actualizado exitosamente.';
            } else {
                // Create new bank
                Bank::create([
                    'name' => $this->name,
                    'code' => $this->code,
                    'type' => $this->type,
                    'is_active' => $this->is_active,
                ]);
                $message = 'Banco creado exitosamente.';
            }

            $this->resetForm();
            $this->dispatch('showToastr', type: 'success', message: $message);
        } catch (\Exception $e) {
            $this->dispatch('showToastr', type: 'error', message: 'Error al guardar el banco: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $bank = Bank::findOrFail($id);
            
            // Verificar si tiene personas asociadas
            if ($bank->people()->count() > 0) {
                $this->dispatch('showToastr', type: 'error', message: 'No se puede eliminar este banco porque tiene personas asociadas.');
                return;
            }

            $bank->delete();
            $this->dispatch('showToastr', type: 'success', message: 'Banco eliminado exitosamente.');
        } catch (\Exception $e) {
            $this->dispatch('showToastr', type: 'error', message: 'Error al eliminar el banco: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->bankId = null;
        $this->name = '';
        $this->code = '';
        $this->type = 'banco';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $banks = Bank::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->when($this->typeFilter, function ($query) {
                $query->where('type', $this->typeFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.bank-list', compact('banks'));
    }
}
