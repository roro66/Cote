<?php

use App\Models\User;
use App\Models\Person;
use App\Models\Account;
use App\Models\Expense;
use App\Services\ExpenseService;
use Spatie\Permission\Models\Role;

it('allows a treasurer to approve an expense and debits account balance', function () {
    // Create treasurer role locally (tests run migrations via RefreshDatabase)
    Role::firstOrCreate(['name' => 'treasurer']);

    // Create a user with treasurer role
    $user = User::factory()->create();
    $user->assignRole('treasurer');

    // Create a person and an account for that person with an initial balance
    $person = Person::factory()->create();
    $account = Account::factory()->create([
        'type' => 'person',
        'person_id' => $person->id,
        'balance' => 100000,
    ]);

    // Create expense via service with required fields
    $service = new ExpenseService();
    $expense = $service->createExpense([
        'account_id' => $account->id,
        'submitted_by' => $person->id,
        'total_amount' => 5000,
        'description' => 'Prueba automatizada',
        'expense_date' => now()->toDateString(),
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    expect($expense)->toBeInstanceOf(Expense::class);
    expect($expense->status)->toBe('submitted');

    // Approve as treasurer (pass user id)
    $service->approveExpense($expense, $user->id);

    $expense->refresh();
    $account->refresh();

    expect($expense->status)->toBe('approved');
    expect((float)$account->balance)->toBe(95000.0);
});
