<?php

use App\Models\Account;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

function boss_user(): User
{
    create_role('boss');
    $user = User::factory()->create();
    $user->assignRole('boss');

    return $user;
}

function account_payload(Account $account, array $overrides = []): array
{
    return array_merge([
        'name' => $account->name,
        'type' => $account->type,
        'is_enabled' => 1,
    ], $overrides);
}

it('keeps the balance when a treasurer edits an account', function () {
    $account = Account::factory()->create(['type' => 'treasury', 'balance' => 1000]);

    $this->actingAs(create_treasurer_user())
        ->put(route('accounts.update', $account), account_payload($account, ['name' => 'Tesorería Central']))
        ->assertRedirect(route('accounts.index'));

    $account->refresh();
    expect($account->name)->toBe('Tesorería Central');
    expect((float) $account->balance)->toBe(1000.0);
});

it('rejects a manual balance change from a treasurer', function () {
    $account = Account::factory()->create(['type' => 'treasury', 'balance' => 1000]);

    $this->actingAs(create_treasurer_user())
        ->put(route('accounts.update', $account), account_payload($account, ['balance' => 5000]))
        ->assertSessionHasErrors('balance');

    expect((float) $account->refresh()->balance)->toBe(1000.0);
});

it('requires a reason for a manual balance adjustment by the boss', function () {
    $account = Account::factory()->create(['type' => 'treasury', 'balance' => 1000]);

    $this->actingAs(boss_user())
        ->put(route('accounts.update', $account), account_payload($account, ['balance' => 5000]))
        ->assertSessionHasErrors('balance_adjustment_reason');

    expect((float) $account->refresh()->balance)->toBe(1000.0);
});

it('records the boss manual balance adjustment with its reason', function () {
    $account = Account::factory()->create(['type' => 'treasury', 'balance' => 1000]);
    $boss = boss_user();

    $this->actingAs($boss)
        ->put(route('accounts.update', $account), account_payload($account, [
            'balance' => 5000,
            'balance_adjustment_reason' => 'Cuadre con cartola bancaria',
        ]))
        ->assertRedirect(route('accounts.index'));

    expect((float) $account->refresh()->balance)->toBe(5000.0);

    $log = Activity::where('log_name', 'balance_adjustment')->sole();
    expect($log->causer_id)->toBe($boss->id);
    expect($log->properties['reason'])->toBe('Cuadre con cartola bancaria');
    expect((float) $log->properties['old'])->toBe(1000.0);
});
