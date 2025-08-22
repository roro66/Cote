<?php

namespace App\Livewire;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Team;
use Livewire\Component;
use Livewire\WithFileUploads;

class ExpenseForm extends Component
{
    use WithFileUploads;

    public $expenseId = null;
    public $team_id = '';
    public $description = '';
    public $reference = '';
    public $currency = 'PEN';
    public $status = 'pending';
    
    // Expense items
    public $items = [];
    public $newItem = [
        'description' => '',
        'amount' => '',
        'currency' => 'PEN',
        'receipt_number' => '',
    ];

    protected $rules = [
        'team_id' => 'required|exists:teams,id',
        'description' => 'required|string|max:500',
        'reference' => 'nullable|string|max:255',
        'currency' => 'required|in:PEN,USD,EUR',
        'status' => 'required|in:pending,approved,rejected',
        'items.*.description' => 'required|string|max:255',
        'items.*.amount' => 'required|numeric|min:0.01',
        'items.*.currency' => 'required|in:PEN,USD,EUR',
        'items.*.receipt_number' => 'nullable|string|max:100',
    ];

    protected $messages = [
        'team_id.required' => 'Debe seleccionar un equipo.',
        'description.required' => 'La descripción es obligatoria.',
        'items.*.description.required' => 'La descripción del item es obligatoria.',
        'items.*.amount.required' => 'El monto del item es obligatorio.',
        'items.*.amount.numeric' => 'El monto debe ser un número.',
        'items.*.amount.min' => 'El monto debe ser mayor a 0.',
    ];

    public function mount($expenseId = null)
    {
        if ($expenseId) {
            $this->expenseId = $expenseId;
            $expense = Expense::with('expenseItems')->findOrFail($expenseId);
            $this->team_id = $expense->team_id;
            $this->description = $expense->description;
            $this->reference = $expense->reference;
            $this->currency = $expense->currency;
            $this->status = $expense->status;
            
            // Load expense items
            $this->items = $expense->expenseItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'currency' => $item->currency,
                    'receipt_number' => $item->receipt_number,
                ];
            })->toArray();
        }
    }

    public function addItem()
    {
        $this->validate([
            'newItem.description' => 'required|string|max:255',
            'newItem.amount' => 'required|numeric|min:0.01',
            'newItem.currency' => 'required|in:PEN,USD,EUR',
        ], [
            'newItem.description.required' => 'La descripción del item es obligatoria.',
            'newItem.amount.required' => 'El monto del item es obligatorio.',
            'newItem.amount.numeric' => 'El monto debe ser un número.',
            'newItem.amount.min' => 'El monto debe ser mayor a 0.',
        ]);

        $this->items[] = $this->newItem;
        $this->newItem = [
            'description' => '',
            'amount' => '',
            'currency' => 'PEN',
            'receipt_number' => '',
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save()
    {
        $this->validate();

        if (empty($this->items)) {
            $this->addError('items', 'Debe agregar al menos un item a la rendición.');
            return;
        }

        $totalAmount = collect($this->items)->sum('amount');

        if ($this->expenseId) {
            // Update existing expense
            $expense = Expense::findOrFail($this->expenseId);
            $expense->update([
                'team_id' => $this->team_id,
                'description' => $this->description,
                'reference' => $this->reference,
                'total_amount' => $totalAmount,
                'currency' => $this->currency,
                'status' => $this->status,
            ]);

            // Delete existing items and create new ones
            $expense->expenseItems()->delete();
        } else {
            // Create new expense
            $expense = Expense::create([
                'team_id' => $this->team_id,
                'description' => $this->description,
                'reference' => $this->reference,
                'total_amount' => $totalAmount,
                'currency' => $this->currency,
                'status' => $this->status,
            ]);
        }

        // Create expense items
        foreach ($this->items as $item) {
            $expense->expenseItems()->create([
                'description' => $item['description'],
                'amount' => $item['amount'],
                'currency' => $item['currency'],
                'receipt_number' => $item['receipt_number'],
            ]);
        }

        session()->flash('message', $this->expenseId ? 'Rendición actualizada correctamente.' : 'Rendición creada correctamente.');

        return redirect()->route('expenses.index');
    }

    public function render()
    {
        $teams = Team::where('is_enabled', true)->orderBy('name')->get();

        return view('livewire.expense-form', compact('teams'));
    }
}
