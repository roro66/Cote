<?php

use App\Models\Account;
use App\Models\Expense;
use App\Models\Person;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountStatementService;

it('lists approved movements with the running balance', function () {
    $person = Person::factory()->create();
    $treasury = Account::factory()->create(['type' => 'treasury', 'balance' => 0]);
    $account = Account::factory()->create(['type' => 'person', 'person_id' => $person->id, 'balance' => 70_000]);
    $creator = User::factory()->create();

    Transaction::create([
        'transaction_number' => 'TXN-T-1', 'type' => 'transfer', 'from_account_id' => $treasury->id,
        'to_account_id' => $account->id, 'amount' => 100_000, 'status' => 'approved',
        'approved_at' => now()->subDays(3), 'created_by' => $creator->id, 'description' => 'Fondos',
    ]);
    Transaction::create([
        'transaction_number' => 'TXN-T-2', 'type' => 'transfer', 'from_account_id' => $treasury->id,
        'to_account_id' => $account->id, 'amount' => 999_999, 'status' => 'pending',
        'created_by' => $creator->id, 'description' => 'Pendiente, no cuenta',
    ]);
    Expense::create([
        'expense_number' => 'RND-T-1', 'account_id' => $account->id, 'submitted_by' => $person->id,
        'total_amount' => 30_000, 'description' => 'Combustible', 'expense_date' => now()->toDateString(),
        'status' => 'approved', 'reviewed_at' => now()->subDay(),
    ]);

    $statement = app(AccountStatementService::class)->build($account);

    expect($statement['movements']->pluck('reference')->all())->toBe(['RND-T-1', 'TXN-T-1']);
    expect($statement['movements']->pluck('balance_after')->all())->toBe([70000.0, 100000.0]);
    expect($statement['total_in'])->toBe(100000.0);
    expect($statement['total_out'])->toBe(30000.0);
    expect($statement['opening_balance'])->toBe(0.0);

    $this->actingAs(create_treasurer_user())
        ->get(route('accounts.movements', $account))
        ->assertOk()
        ->assertSee('TXN-T-1')
        ->assertSee('RND-T-1')
        ->assertDontSee('TXN-T-2');
});

it('flags balance not explained by recorded movements', function () {
    $account = Account::factory()->create(['type' => 'treasury', 'balance' => 50_000]);

    expect(app(AccountStatementService::class)->build($account)['opening_balance'])->toBe(50000.0);
});

it('forbids users without role from viewing account movements', function () {
    $account = Account::factory()->create(['type' => 'treasury']);

    $this->actingAs(User::factory()->create())
        ->get(route('accounts.movements', $account))
        ->assertForbidden();
});

it('shows pending amounts and negative accounts on the dashboard for the treasurer', function () {
    $person = Person::factory()->create(['first_name' => 'Rosa', 'last_name' => 'Negativa']);
    Account::factory()->create(['type' => 'person', 'person_id' => $person->id, 'balance' => -15_000, 'is_enabled' => true]);

    $this->actingAs(create_treasurer_user())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Requiere atención')
        ->assertSee('Rosa Negativa')
        ->assertSee('$15.000')
        ->assertSee('Por rendir');

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Requiere atención');
});
