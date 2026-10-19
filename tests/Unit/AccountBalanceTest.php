<?php

use App\Models\Account;

it('decrements account balance correctly', function () {
    // Create account directly to avoid relying on factories
    $account = Account::create([
        'name' => 'Cuenta prueba unit',
        'type' => 'person',
        'balance' => 100000,
        'is_enabled' => true,
    ]);

    // Decrement balance
    $account->decrement('balance', 5000);
    $account->refresh();

    expect((float)$account->balance)->toBe(95000.0);
});

it('describes a worker balance in petty cash terms', function () {
    $account = new \App\Models\Account(['type' => 'person']);

    $account->balance = 5000;
    expect($account->balance_status['label'])->toBe('Por rendir');

    $account->balance = -2500;
    expect($account->balance_status['label'])->toBe('Se le debe');
    expect($account->balance_abs_formatted)->toBe('$2.500');

    $account->balance = 0;
    expect($account->balance_status['label'])->toBe('Al día');
});
