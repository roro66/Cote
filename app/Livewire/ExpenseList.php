<?php

namespace App\Livewire;

use App\Models\Expense;
use App\Models\Team;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseList extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;
    public $statusFilter = '';
    public $teamFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'statusFilter' => ['except' => ''],
        'teamFilter' => ['except' => ''],
    ];

    public function updatingSearch()
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

    public function approveExpense($id)
    {
        $expense = Expense::findOrFail($id);
        $expense->update(['status' => 'approved']);
        
        session()->flash('message', 'Rendición aprobada correctamente.');
    }

    public function rejectExpense($id)
    {
        $expense = Expense::findOrFail($id);
        $expense->update(['status' => 'rejected']);
        
        session()->flash('message', 'Rendición rechazada.');
    }

    public function render()
    {
        $expenses = Expense::query()
            ->with(['team', 'expenseItems'])
            ->when($this->search, function ($query) {
                $query->where('description', 'like', '%' . $this->search . '%')
                      ->orWhere('reference', 'like', '%' . $this->search . '%')
                      ->orWhereHas('team', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->teamFilter, function ($query) {
                $query->where('team_id', $this->teamFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $teams = Team::where('is_enabled', true)->orderBy('name')->get();

        return view('livewire.expense-list', compact('expenses', 'teams'));
    }
}
