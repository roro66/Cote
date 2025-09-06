<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;

uses(RefreshDatabase::class);

it('approves a transfer when there are sufficient funds', function () {
    $user = User::factory()->create();

    $person = \App\Models\Person::create([
        'first_name' => 'Origen',
        'last_name' => 'Cuenta',
        'rut' => '88888888-8',
        'role_type' => 'trabajador',
        'is_enabled' => true,
    ]);
    $from = Account::factory()->create(['balance' => 500_000, 'type' => 'treasury']);
    $to   = Account::factory()->create(['balance' => 100_000, 'type' => 'person', 'person_id' => $person->id]);

    $tx = Transaction::create([
        'from_account_id' => $from->id,
        'to_account_id'   => $to->id,
        'amount'          => 300_000,
        'status'          => 'pending',
        'type'            => 'transfer',
        'created_by'      => $user->id,
        'description'     => 'Transferencia de test',
    ]);

    // Usar el servicio directamente
    $service = app(\App\Services\TransactionService::class);
    $service->approveTransaction($tx, $user->id);

    $tx->refresh();
    expect($tx->status)->toBe('approved');
    expect($from->refresh()->balance)->toBe('200000.00');
    expect($to->refresh()->balance)->toBe('400000.00');
});
