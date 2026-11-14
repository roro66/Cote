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

it('registers a delivery from treasury to a person already approved', function () {
    $treasury = Account::factory()->create(['type' => 'treasury', 'balance' => 500_000]);
    $person = person_account();
    $treasurer = create_treasurer_user();

    $this->actingAs($treasurer)
        ->postJson(route('transactions.store'), transfer_payload($treasury, $person, 100_000))
        ->assertOk()
        ->assertJson(['success' => true]);

    $tx = Transaction::sole();
    expect($tx->status)->toBe('approved')
        ->and($tx->approved_by)->toBe($treasurer->id)
        ->and($tx->type_spanish)->toBe('Entrega')
        ->and((float) $treasury->refresh()->balance)->toBe(400000.0)
        ->and((float) $person->refresh()->balance)->toBe(100000.0);
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

it('registers a deposit into treasury that adds money right away', function () {
    $treasury = Account::factory()->create(['type' => 'treasury', 'balance' => 0]);
    $treasurer = create_treasurer_user();

    $this->actingAs($treasurer)
        ->postJson(route('transactions.store'), [
            'type' => 'deposit',
            'to_account_id' => $treasury->id,
            'amount' => 500_000,
            'description' => 'Transferencia bancaria de la empresa',
        ])
        ->assertOk();

    $deposit = Transaction::sole();
    expect($deposit->status)->toBe('approved');
    expect($deposit->from_account_id)->toBeNull();
    expect((float) $treasury->refresh()->balance)->toBe(500000.0);

    $statement = app(\App\Services\AccountStatementService::class)->build($treasury);
    expect($statement['movements']->first()['kind'])->toBe('Ingreso a caja chica');
    expect($statement['opening_balance'])->toBe(0.0);
});

it('only accepts deposits into treasury and without source account', function () {
    $treasury = Account::factory()->create(['type' => 'treasury', 'balance' => 0]);
    $worker = person_account();

    $this->actingAs(create_treasurer_user())
        ->postJson(route('transactions.store'), ['type' => 'deposit', 'to_account_id' => $worker->id, 'amount' => 1000, 'description' => 'x'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to_account_id');

    $this->actingAs(create_treasurer_user())
        ->postJson(route('transactions.store'), ['type' => 'deposit', 'from_account_id' => $worker->id, 'to_account_id' => $treasury->id, 'amount' => 1000, 'description' => 'x'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('from_account_id');

    expect(Transaction::count())->toBe(0);
});
