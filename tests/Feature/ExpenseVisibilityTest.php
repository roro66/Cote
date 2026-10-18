<?php

use App\Models\Account;
use App\Models\Expense;
use App\Models\Person;
use App\Models\User;

function technician_with_expense(string $description): array
{
    $person = Person::factory()->create();
    $account = Account::factory()->create(['type' => 'person', 'person_id' => $person->id, 'balance' => 123_456]);
    $user = User::factory()->create(['person_id' => $person->id]);
    $expense = Expense::create([
        'expense_number' => 'RND-' . uniqid(),
        'account_id' => $account->id,
        'submitted_by' => $person->id,
        'total_amount' => 1000,
        'description' => $description,
        'expense_date' => now()->toDateString(),
        'status' => 'submitted',
    ]);

    return [$user, $account, $expense];
}

it('lists only the technician own expenses', function () {
    [$user] = technician_with_expense('Gasto propio');
    technician_with_expense('Gasto ajeno');

    $this->actingAs($user)
        ->getJson(route('datatables.expenses'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1)
        ->assertSee('Gasto propio')
        ->assertDontSee('Gasto ajeno');
});

it('lets the treasurer list every expense', function () {
    technician_with_expense('Gasto uno');
    technician_with_expense('Gasto dos');

    $this->actingAs(create_treasurer_user())
        ->getJson(route('datatables.expenses'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertJsonPath('recordsTotal', 2);
});

it('forbids opening or editing another person expense', function () {
    [$user, , $own] = technician_with_expense('Gasto propio');
    [, , $other] = technician_with_expense('Gasto ajeno');

    $this->actingAs($user)->get(route('expenses.show', $own))->assertOk();
    $this->actingAs($user)->get(route('expenses.show', $other))->assertForbidden();
    $this->actingAs($user)->getJson(route('expenses.details', $other))->assertForbidden();
    $this->actingAs($user)->get(route('expenses.edit', $other))->assertForbidden();
    $this->actingAs($user)->delete(route('expenses.destroy', $other))->assertForbidden();

    expect($other->refresh()->is_enabled)->not->toBeFalse();
});

it('forbids submitting an expense on another person account', function () {
    [$user] = technician_with_expense('Gasto propio');
    [, $otherAccount] = technician_with_expense('Gasto ajeno');

    $this->actingAs($user)->post(route('expenses.store'), [
        'account_id' => $otherAccount->id,
        'description' => 'Intento',
        'currency' => 'CLP',
        'items' => [['description' => 'x', 'amount' => 10, 'currency' => 'CLP', 'document_type' => 'boleta', 'vendor_name' => 'x']],
    ])->assertForbidden();

    expect(Expense::where('description', 'Intento')->exists())->toBeFalse();
});

it('limits account details to the technician own account', function () {
    [$user, $own] = technician_with_expense('Gasto propio');
    [, $other] = technician_with_expense('Gasto ajeno');

    $this->actingAs($user)->getJson(route('accounts.show', $own))->assertOk();
    $this->actingAs($user)->getJson(route('accounts.show', $other))->assertForbidden();
});

it('does not leak global balances in the dashboard for a technician', function () {
    [$user] = technician_with_expense('Gasto propio');
    [, , $other] = technician_with_expense('Gasto ajeno');
    Account::factory()->create(['type' => 'treasury', 'balance' => 987_654_321]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Gasto propio')
        ->assertDontSee('Gasto ajeno')
        ->assertDontSee($other->expense_number)
        ->assertDontSee('987654321')
        ->assertDontSee('987.654.321');
});

it('lets the boss link a user to a person', function () {
    create_role('boss');
    $boss = User::factory()->create();
    $boss->assignRole('boss');
    $person = Person::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($boss)->put(route('users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'is_enabled' => 1,
        'person_id' => $person->id,
    ])->assertRedirect();

    expect($user->refresh()->person_id)->toBe($person->id);
});
