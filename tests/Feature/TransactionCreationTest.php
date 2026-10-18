<?php

use App\Models\Account;
use App\Models\Person;
use App\Models\Transaction;

function transfer_payload(Account $from, Account $to, int $amount): array
{
    return [
        'type' => 'transfer',
        'from_account_id' => $from->id,
        'to_account_id' => $to->id,
        'amount' => $amount,
        'description' => 'Transferencia de prueba',
    ];
}

function person_account(int $balance = 0, array $attrs = []): Account
{
    $person = Person::factory()->create();

    return Account::factory()->create(array_merge(['type' => 'person', 'person_id' => $person->id, 'balance' => $balance], $attrs));
}

it('creates a pending transfer from treasury to a person', function () {
    $treasury = Account::factory()->create(['type' => 'treasury', 'balance' => 500_000]);
    $person = person_account();

    $this->actingAs(create_treasurer_user())
        ->postJson(route('transactions.store'), transfer_payload($treasury, $person, 100_000))
        ->assertOk()
        ->assertJson(['success' => true]);

    $tx = Transaction::sole();
    expect($tx->status)->toBe('pending');
    expect((float) $treasury->refresh()->balance)->toBe(500000.0);
});

it('rejects a transfer without enough funds', function () {
    $treasury = Account::factory()->create(['type' => 'treasury', 'balance' => 50_000]);

    $this->actingAs(create_treasurer_user())
        ->postJson(route('transactions.store'), transfer_payload($treasury, person_account(), 100_000))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    expect(Transaction::count())->toBe(0);
});

it('rejects a transfer between two personal accounts', function () {
    $this->actingAs(create_treasurer_user())
        ->postJson(route('transactions.store'), transfer_payload(person_account(500_000), person_account(), 100_000))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to_account_id');

    expect(Transaction::count())->toBe(0);
});

it('only allows the funding account to transfer to treasury', function () {
    $fondeo = person_account(1_000_000, ['is_fondeo' => true]);

    $this->actingAs(create_treasurer_user())
        ->postJson(route('transactions.store'), transfer_payload($fondeo, person_account(), 100_000))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to_account_id');

    $treasury = Account::factory()->create(['type' => 'treasury', 'balance' => 0]);
    $this->actingAs(create_treasurer_user())
        ->postJson(route('transactions.store'), transfer_payload($fondeo, $treasury, 100_000))
        ->assertOk();

    expect(Transaction::count())->toBe(1);
});
