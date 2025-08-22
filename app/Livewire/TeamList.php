<?php

namespace App\Livewire;

use App\Models\Team;
use App\Models\Person;
use Livewire\Component;
use Livewire\WithPagination;

class TeamList extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $showingForm = false;
    public $editing = false;
    public $teamId = null;

    // Form fields
    public $name = '';
    public $description = '';
    public $leader_id = '';
    public $is_enabled = true;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'leader_id' => 'required|exists:people,id',
            'is_enabled' => 'boolean',
        ];
    }

    protected $validationAttributes = [
        'name' => 'nombre',
        'description' => 'descripción',
        'leader_id' => 'líder',
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
        $team = Team::findOrFail($id);
        $this->teamId = $team->id;
        $this->name = $team->name;
        $this->description = $team->description;
        $this->leader_id = $team->leader_id;
        $this->is_enabled = $team->is_enabled;
        $this->showingForm = true;
        $this->editing = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editing) {
            $team = Team::findOrFail($this->teamId);
            $team->update([
                'name' => $this->name,
                'description' => $this->description,
                'leader_id' => $this->leader_id,
                'is_enabled' => $this->is_enabled,
            ]);
            session()->flash('message', 'Equipo actualizado correctamente.');
        } else {
            Team::create([
                'name' => $this->name,
                'description' => $this->description,
                'leader_id' => $this->leader_id,
                'is_enabled' => $this->is_enabled,
            ]);
            session()->flash('message', 'Equipo creado correctamente.');
        }

        $this->resetForm();
        $this->showingForm = false;
    }

    public function delete($id)
    {
        Team::findOrFail($id)->delete();
        session()->flash('message', 'Equipo eliminado correctamente.');
    }

    public function cancel()
    {
        $this->resetForm();
        $this->showingForm = false;
    }

    private function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->leader_id = '';
        $this->is_enabled = true;
        $this->teamId = null;
        $this->editing = false;
    }

    public function render()
    {
        $teams = Team::with(['leader', 'members'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhereHas('leader', function ($subQuery) {
                          $subQuery->where('first_name', 'like', '%' . $this->search . '%')
                                  ->orWhere('last_name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        $people = Person::where('is_enabled', true)
                        ->where('role_type', 'team_leader')
                        ->get();

        return view('livewire.team-list', compact('teams', 'people'));
    }
}
