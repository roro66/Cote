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
