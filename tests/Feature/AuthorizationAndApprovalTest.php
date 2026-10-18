<?php

use App\Livewire\DashboardSummary;
use App\Models\Account;
use App\Models\Person;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

function pending_transfer(int $fromBalance, int $amount): array
{
    $creator = User::factory()->create();
    $person = Person::factory()->create();
    $from = Account::factory()->create(['type' => 'treasury', 'balance' => $fromBalance]);
    $to = Account::factory()->create(['type' => 'person', 'person_id' => $person->id, 'balance' => 0]);

    $tx = Transaction::create([
        'transaction_number' => 'TXN-TEST-' . uniqid(),
        'type' => 'transfer',
        'from_account_id' => $from->id,
        'to_account_id' => $to->id,
        'amount' => $amount,
        'status' => 'pending',
        'created_by' => $creator->id,
        'description' => 'Transferencia de prueba',
    ]);

    return [$tx, $from, $to];
}

it('forbids users without role from approving transactions', function () {
    [$tx, $from, $to] = pending_transfer(500_000, 100_000);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('approvals.transactions.approve', $tx))
        ->assertForbidden();

    expect($tx->refresh()->status)->toBe('pending');
    expect((float) $from->refresh()->balance)->toBe(500000.0);
});

it('forbids users without role from admin tools and account editing', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['type' => 'treasury', 'balance' => 1000]);

    $this->actingAs($user)->post(route('admin.tools.normalize'))->assertForbidden();
    $this->actingAs($user)->put(route('accounts.update', $account), ['balance' => 999_999])->assertForbidden();

    expect((float) $account->refresh()->balance)->toBe(1000.0);
});

it('forbids treasurers from admin tools and user management', function () {
    $treasurer = create_treasurer_user();

    $this->actingAs($treasurer)->get(route('admin.tools'))->assertForbidden();
    $this->actingAs($treasurer)->get(route('users.index'))->assertForbidden();
});

it('still lets users without role use the dashboard and their expenses', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $this->actingAs($user)->get(route('expenses.index'))->assertOk();
});

it('lets a treasurer approve a transfer and moves balances once', function () {
    [$tx, $from, $to] = pending_transfer(500_000, 100_000);
    $treasurer = create_treasurer_user();

    $this->actingAs($treasurer)
        ->postJson(route('approvals.transactions.approve', $tx))
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->actingAs($treasurer)
        ->postJson(route('approvals.transactions.approve', $tx))
        ->assertStatus(422);

    expect($tx->refresh()->status)->toBe('approved');
    expect((float) $from->refresh()->balance)->toBe(400000.0);
    expect((float) $to->refresh()->balance)->toBe(100000.0);
});

it('rejects approval when the source account lacks funds', function () {
    [$tx, $from, $to] = pending_transfer(50_000, 100_000);
    $treasurer = create_treasurer_user();

    $this->actingAs($treasurer)
        ->postJson(route('approvals.transactions.approve', $tx))
        ->assertStatus(422)
        ->assertJson(['message' => 'La cuenta origen no tiene fondos suficientes para esta transacción.']);

    expect($tx->refresh()->status)->toBe('pending');
    expect((float) $from->refresh()->balance)->toBe(50000.0);
    expect((float) $to->refresh()->balance)->toBe(0.0);
});

it('does not let a stale model approve an already approved transaction', function () {
    [$tx, $from] = pending_transfer(500_000, 100_000);
    $treasurer = create_treasurer_user();
    $stale = Transaction::find($tx->id);

    app(\App\Services\TransactionService::class)->approveTransaction($tx, $treasurer->id);

    expect(fn () => app(\App\Services\TransactionService::class)->approveTransaction($stale, $treasurer->id))
        ->toThrow(\DomainException::class);
    expect((float) $from->refresh()->balance)->toBe(400000.0);
});

it('blocks the dashboard approve action for users without role', function () {
    [$tx] = pending_transfer(500_000, 100_000);

    Livewire::actingAs(User::factory()->create())
        ->test(DashboardSummary::class)
        ->call('approveTransaction', $tx->id)
        ->assertForbidden();

    expect($tx->refresh()->status)->toBe('pending');
});

it('does not let disabled users log in', function () {
    $user = User::factory()->create(['is_enabled' => false]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Tu cuenta está desactivada.']);

    $this->assertGuest();
});

it('logs out a disabled user that still has an open session', function () {
    $user = User::factory()->create(['is_enabled' => false]);

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
});
