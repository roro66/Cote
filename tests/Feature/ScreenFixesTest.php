<?php

use App\Livewire\DashboardSummary;
use App\Livewire\TransactionForm;
use App\Models\Account;
use App\Models\Bank;
use App\Models\Expense;
use App\Models\Person;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportService;
use Livewire\Livewire;

function expense_for(Account $account, string $status, bool $enabled = true): Expense
{
    return Expense::create([
        'expense_number' => 'RND-TEST-' . uniqid(),
        'account_id' => $account->id,
        'submitted_by' => $account->person_id,
        'total_amount' => 1000,
        'description' => "Rendición {$status}",
        'expense_date' => now()->toDateString(),
        'status' => $status,
        'is_enabled' => $enabled,
    ]);
}

it('sends the rejection reason with the field name the server expects', function () {
    $this->actingAs(create_treasurer_user())
        ->get(route('approvals.index'))
        ->assertOk()
        ->assertSee('rejection_reason: reason', false)
        ->assertDontSee('reject_reason', false);
});

it('rejects an expense from the approvals screen request', function () {
    [, $account] = create_person_with_account();
    $expense = expense_for($account, 'submitted');

    $this->actingAs(create_treasurer_user())
        ->postJson(route('approvals.expenses.reject', $expense), ['rejection_reason' => 'Falta la boleta'])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($expense->refresh()->status)->toBe('rejected');
});

it('shows the expense status in spanish on the approvals table', function () {
    [, $account] = create_person_with_account();
    expense_for($account, 'submitted');

    $this->actingAs(create_treasurer_user())
        ->getJson(route('datatables.approvals.expenses'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonPath('data.0.status', 'Enviado');
});

it('creates a bank with its type', function () {
    $this->actingAs(create_treasurer_user())
        ->postJson(route('banks.store'), ['code' => 'NUE', 'name' => 'Banco Nuevo', 'type' => 'cooperativa'])
        ->assertOk();

    expect(Bank::where('code', 'NUE')->value('type'))->toBe('cooperativa');
});

it('asks for the bank type', function () {
    $this->actingAs(create_treasurer_user())
        ->postJson(route('banks.store'), ['code' => 'NUE', 'name' => 'Banco Nuevo'])
        ->assertJsonValidationErrors('type');
});

it('reports a repeated rut written with dots', function () {
    Person::factory()->create(['rut' => '12.345.678-5']);

    $this->actingAs(create_treasurer_user())
        ->postJson(route('people.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'rut' => '12.345.678-5',
            'email' => 'ana@example.com',
            'role_type' => 'trabajador',
        ])
        ->assertJsonValidationErrors(['rut' => 'Este RUT ya está registrado.']);
});

it('removes every role when the boss unchecks them all', function () {
    create_role('boss');
    $boss = User::factory()->create();
    $boss->assignRole('boss');
    $treasurer = create_treasurer_user();

    $this->actingAs($boss)
        ->put(route('users.update', $treasurer), [
            'name' => $treasurer->name,
            'email' => $treasurer->email,
            'is_enabled' => 1,
            'roles' => '',
        ], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
        ->assertOk();

    expect($treasurer->refresh()->roles)->toHaveCount(0);
});

it('returns the single treasurer rule on the roles field', function () {
    create_role('boss');
    $boss = User::factory()->create();
    $boss->assignRole('boss');
    create_treasurer_user();
    $other = User::factory()->create();

    $this->actingAs($boss)
        ->putJson(route('users.update', $other), [
            'name' => $other->name,
            'email' => $other->email,
            'roles' => ['treasurer'],
        ])
        ->assertJsonValidationErrors('roles');
});

it('keeps an inactive linked person available when editing a user', function () {
    create_role('boss');
    $boss = User::factory()->create();
    $boss->assignRole('boss');
    $inactive = Person::factory()->create(['is_enabled' => false, 'first_name' => 'Inactiva']);
    User::factory()->create(['person_id' => $inactive->id]);

    $this->actingAs($boss)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('value="' . $inactive->id . '"', false)
        ->assertSee('— inactiva');
});

it('lets the treasurer edit a pending transfer', function () {
    [$tx, $from, $to] = pending_transfer(500_000, 100_000);
    $this->actingAs(create_treasurer_user());

    Livewire::test(TransactionForm::class, ['transactionId' => $tx->id])
        ->set('amount', 150_000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('transactions.index'));

    expect((float) $tx->refresh()->amount)->toBe(150000.0)
        ->and($tx->status)->toBe('pending');
});

it('lets a pending cash deposit be edited', function () {
    $treasury = Account::factory()->create(['type' => 'treasury', 'balance' => 0]);
    $tx = Transaction::create([
        'transaction_number' => 'TXN-TEST-' . uniqid(),
        'type' => 'deposit',
        'to_account_id' => $treasury->id,
        'amount' => 10_000,
        'status' => 'pending',
        'created_by' => User::factory()->create()->id,
        'description' => 'Cheque',
    ]);
    $this->actingAs(create_treasurer_user());

    Livewire::test(TransactionForm::class, ['transactionId' => $tx->id])
        ->assertSet('type', 'deposit')
        ->set('amount', 20_000)
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $tx->refresh()->amount)->toBe(20000.0)
        ->and($tx->from_account_id)->toBeNull();
});

it('leaves deleted expenses out of the dashboard', function () {
    [, $account] = create_person_with_account();
    expense_for($account, 'submitted');
    expense_for($account, 'submitted', enabled: false);
    $this->actingAs(create_treasurer_user());

    Livewire::test(DashboardSummary::class)
        ->assertSet('pendingExpenses', 1)
        ->assertSet('pendingExpensesAmount', 1000.0);
});

it('counts approved and pending expenses as all, but not rejected or deleted ones', function () {
    [, $account] = create_person_with_account();
    foreach (['approved', 'submitted', 'rejected'] as $status) {
        expense_for($account, $status)->items()->create([
            'description' => 'Ítem', 'amount' => 1000, 'document_type' => 'boleta',
            'vendor_name' => 'Proveedor', 'expense_date' => now()->toDateString(),
        ]);
    }
    expense_for($account, 'submitted', enabled: false);

    $today = now()->toDateString();
    $report = app(ReportService::class);

    expect($report->generateMonthlyExpenseReport($today, $today, 'summary', 'all')['total_expenses'])->toBe(2)
        ->and($report->generateMonthlyExpenseReport($today, $today, 'summary', 'approved_only')['total_expenses'])->toBe(1);
});
