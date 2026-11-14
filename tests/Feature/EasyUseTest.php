<?php

use App\Livewire\CashMovement;
use App\Livewire\DashboardSummary;
use App\Livewire\ExpenseReview;
use App\Models\Account;
use App\Models\Expense;
use App\Models\PeriodClosing;
use App\Models\Person;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\Tools\PrepareMovementTool;
use App\Services\CashBookService;
use App\Services\TransactionService;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

function easy_worker(int $balance = 0, string $first = 'Juan', string $last = 'Pérez'): Account
{
    $person = Person::factory()->create(['first_name' => $first, 'last_name' => $last, 'is_enabled' => true]);

    return Account::create(['name' => "$first $last", 'type' => 'person', 'person_id' => $person->id, 'balance' => $balance, 'is_enabled' => true]);
}

function easy_treasury(int $balance): Account
{
    return Account::create(['name' => 'Tesorería', 'type' => 'treasury', 'balance' => $balance, 'is_enabled' => true]);
}

function easy_expense(Account $account, string $status = 'submitted', int $amount = 30000): Expense
{
    $expense = Expense::create([
        'expense_number' => 'RND-T-' . uniqid(), 'account_id' => $account->id, 'submitted_by' => $account->person_id,
        'total_amount' => $amount, 'description' => 'Materiales', 'expense_date' => now()->toDateString(),
        'status' => $status, 'submitted_at' => now(), 'is_enabled' => true,
    ]);
    $expense->items()->create(['description' => 'Clavos', 'amount' => $amount, 'document_type' => 'boleta', 'vendor_name' => 'Ferretería', 'expense_date' => now()->toDateString()]);

    return $expense;
}

it('delivers money step by step and leaves it approved', function () {
    $treasury = easy_treasury(200000);
    $worker = easy_worker();
    $this->actingAs(create_treasurer_user());

    Livewire::test(CashMovement::class, ['mode' => 'entregar'])
        ->call('choose', $worker->id)
        ->set('amount', '50000')
        ->call('toDetail')
        ->assertSet('step', 'detalle')
        ->call('toConfirm')
        ->assertSee('Vas a entregar')
        ->assertSee('$150.000')
        ->call('save')
        ->assertSet('step', 'listo');

    $tx = Transaction::sole();
    expect($tx->status)->toBe('approved')
        ->and((float) $treasury->refresh()->balance)->toBe(150000.0)
        ->and((float) $worker->refresh()->balance)->toBe(50000.0);
});

it('warns when the cash box does not have enough money', function () {
    easy_treasury(10000);
    $worker = easy_worker();
    $this->actingAs(create_treasurer_user());

    Livewire::test(CashMovement::class, ['mode' => 'entregar'])
        ->call('choose', $worker->id)
        ->set('amount', '50000')
        ->call('toDetail')
        ->assertHasErrors('amount')
        ->assertSee('La caja solo tiene $10.000');

    expect(Transaction::count())->toBe(0);
});

it('does not accept a return larger than what the worker holds', function () {
    easy_treasury(0);
    $worker = easy_worker(20000);
    $this->actingAs(create_treasurer_user());

    Livewire::test(CashMovement::class, ['mode' => 'devolucion'])
        ->call('choose', $worker->id)
        ->set('amount', '30000')
        ->call('toDetail')
        ->assertSee('Juan solo tiene $20.000 en su poder.');
});

it('registers money coming into the cash box', function () {
    $treasury = easy_treasury(0);
    $this->actingAs(create_treasurer_user());

    Livewire::test(CashMovement::class, ['mode' => 'ingreso'])
        ->assertSet('step', 'monto')
        ->set('amount', '300000')
        ->call('toDetail')
        ->call('toConfirm')
        ->assertHasErrors('description')
        ->call('useDetail', 'Cheque de la empresa')
        ->call('toConfirm')
        ->call('save');

    expect((float) $treasury->refresh()->balance)->toBe(300000.0);
});

it('opens the wizard already filled from a link', function () {
    easy_treasury(100000);
    $worker = easy_worker();
    $this->actingAs(create_treasurer_user());

    Livewire::test(CashMovement::class, ['mode' => 'entregar', 'persona' => $worker->person_id, 'monto' => '40000', 'detalle' => 'Materiales'])
        ->assertSet('step', 'confirmar')
        ->assertSee('Vas a entregar');
});

it('lets you undo a delivery right after saving it', function () {
    $treasury = easy_treasury(100000);
    $worker = easy_worker();
    $this->actingAs(create_treasurer_user());

    Livewire::test(CashMovement::class, ['mode' => 'entregar', 'persona' => $worker->person_id, 'monto' => '40000', 'detalle' => 'Error'])
        ->call('save')
        ->call('annulDone', 'Me equivoqué de persona');

    expect(Transaction::sole()->status)->toBe('annulled')
        ->and((float) $treasury->refresh()->balance)->toBe(100000.0)
        ->and((float) $worker->refresh()->balance)->toBe(0.0);
});

it('annuls an approved movement and puts the balances back', function () {
    $treasury = easy_treasury(100000);
    $worker = easy_worker();
    $treasurer = create_treasurer_user();
    $tx = app(TransactionService::class)->createApproved(['type' => 'transfer', 'from_account_id' => $treasury->id, 'to_account_id' => $worker->id, 'amount' => 30000, 'description' => 'x'], $treasurer->id);

    $this->actingAs($treasurer)
        ->postJson(route('transactions.annul', $tx), ['reason' => 'Era para otra persona'])
        ->assertOk();

    expect($tx->refresh()->status)->toBe('annulled')
        ->and($tx->annul_reason)->toBe('Era para otra persona')
        ->and((float) $treasury->refresh()->balance)->toBe(100000.0);
});

it('does not annul a deposit the cash box already spent', function () {
    $treasury = easy_treasury(0);
    $worker = easy_worker();
    $treasurer = create_treasurer_user();
    $service = app(TransactionService::class);
    $deposit = $service->createApproved(['type' => 'deposit', 'to_account_id' => $treasury->id, 'amount' => 50000, 'description' => 'Cheque'], $treasurer->id);
    $service->createApproved(['type' => 'transfer', 'from_account_id' => $treasury->id, 'to_account_id' => $worker->id, 'amount' => 40000, 'description' => 'x'], $treasurer->id);

    $this->actingAs($treasurer)
        ->postJson(route('transactions.annul', $deposit), ['reason' => 'x'])
        ->assertStatus(422);

    expect($deposit->refresh()->status)->toBe('approved');
});

it('annuls an approved expense report so it can be corrected', function () {
    $worker = easy_worker(50000);
    $expense = easy_expense($worker, 'approved', 30000);
    $worker->update(['balance' => 20000]);

    $this->actingAs(create_treasurer_user())
        ->post(route('expenses.annul', $expense), ['reason' => 'Boleta repetida'])
        ->assertRedirect(route('expenses.show', $expense));

    expect($expense->refresh()->status)->toBe('rejected')
        ->and($expense->rejection_reason)->toContain('Boleta repetida')
        ->and((float) $worker->refresh()->balance)->toBe(50000.0);
});

it('builds the cash book of a month with opening and closing balances', function () {
    $treasury = easy_treasury(0);
    $worker = easy_worker();
    $treasurer = create_treasurer_user();
    $service = app(TransactionService::class);
    $service->createApproved(['type' => 'deposit', 'to_account_id' => $treasury->id, 'amount' => 100000, 'description' => 'Cheque'], $treasurer->id);
    $service->createApproved(['type' => 'transfer', 'from_account_id' => $treasury->id, 'to_account_id' => $worker->id, 'amount' => 30000, 'description' => 'Entrega'], $treasurer->id);

    $book = app(CashBookService::class)->build(CarbonImmutable::now('America/Santiago'));

    expect($book['opening'])->toBe(0.0)
        ->and($book['total_in'])->toBe(100000.0)
        ->and($book['total_out'])->toBe(30000.0)
        ->and($book['closing'])->toBe(70000.0)
        ->and($book['rows']->pluck('kind')->all())->toBe(['Ingreso a caja chica', 'Entrega']);

    $this->actingAs($treasurer)->get(route('cashbook.index'))->assertOk()->assertSee('$70.000');
    $this->actingAs($treasurer)->get(route('cashbook.export'))->assertOk();
});

it('creates the internal account together with the person', function () {
    $this->actingAs(create_treasurer_user())
        ->postJson(route('people.store'), [
            'first_name' => 'Rosa', 'last_name' => 'Díaz', 'rut' => '11.111.111-1',
            'email' => 'rosa@example.com', 'role_type' => 'trabajador', 'is_enabled' => 1,
        ], ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk();

    $person = Person::where('email', 'rosa@example.com')->sole();
    expect(Account::where('person_id', $person->id)->where('type', 'person')->count())->toBe(1);
});

it('shows each worker with what they hold or what we owe them', function () {
    easy_worker(15000, 'Ana', 'Rojas');
    easy_worker(-8000, 'Luis', 'Soto');
    $withoutAccount = Person::factory()->create(['first_name' => 'Sin', 'last_name' => 'Cuenta', 'is_enabled' => true]);
    $treasurer = create_treasurer_user();

    $this->actingAs($treasurer)->get(route('workers.index'))
        ->assertOk()
        ->assertSee('Tiene')
        ->assertSee('$15.000')
        ->assertSee('Le debemos')
        ->assertSee('Crear su cuenta');

    $this->actingAs($treasurer)->post(route('workers.account', $withoutAccount))->assertRedirect(route('workers.index'));
    expect(Account::where('person_id', $withoutAccount->id)->exists())->toBeTrue();
});

it('reviews expense reports one by one', function () {
    $worker = easy_worker(50000);
    $first = easy_expense($worker);
    $second = easy_expense($worker);
    $this->actingAs(create_treasurer_user());

    Livewire::test(ExpenseReview::class)
        ->assertSee('Quedan')
        ->call('approve', $first->id)
        ->call('startReturn')
        ->call('addReason', 'La foto no se lee')
        ->call('sendBack', $second->id)
        ->assertSee('No hay rendiciones por revisar');

    expect($first->refresh()->status)->toBe('approved')
        ->and($second->refresh()->status)->toBe('rejected')
        ->and($second->rejection_reason)->toBe('La foto no se lee')
        ->and((float) $worker->refresh()->balance)->toBe(20000.0);
});

it('closes a month counting bills and coins', function () {
    $this->travelTo(now()->setDate(2026, 11, 14));
    easy_treasury(0);

    $this->actingAs(create_treasurer_user())->post(route('closings.store'), [
        'month' => '2026-10',
        'count' => [20000 => 2, 1000 => 3, 100 => 5],
        'bank' => 10000,
    ])->assertRedirect();

    $closing = PeriodClosing::sole();
    expect((float) $closing->treasury_counted)->toBe(53500.0)
        ->and($closing->count_detail['banco'])->toEqual(10000);
});

it('prepares a delivery for the assistant without saving it', function () {
    easy_treasury(100000);
    $worker = easy_worker();
    $this->actingAs(create_treasurer_user());

    $result = app(PrepareMovementTool::class)->run(['tipo' => 'entrega', 'persona' => 'juan perez', 'monto' => 50000, 'detalle' => 'Materiales']);

    expect($result['ok'])->toBeTrue()
        ->and($result['url'])->toContain('/caja/entregar')
        ->and($result['url'])->toContain('persona=' . $worker->person_id)
        ->and(Transaction::count())->toBe(0);
});

it('lists what the treasurer has to do today', function () {
    $worker = easy_worker(50000);
    easy_expense($worker);
    $this->actingAs(create_treasurer_user());

    Livewire::test(DashboardSummary::class)->assertSee('Hay 1 rendición por revisar.');
});

it('tells a worker what they hold', function () {
    $worker = easy_worker(25000);
    $this->actingAs(User::factory()->create(['person_id' => $worker->person_id]));

    Livewire::test(DashboardSummary::class)->assertSee('Tienes $25.000 de la empresa');
});

it('shows quick buttons by profile in the top bar', function () {
    $this->actingAs(create_treasurer_user())->get(route('dashboard'))
        ->assertSee('Entregar dinero')->assertSee('Libro de caja')->assertSee('Revisar rendiciones');

    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertSee('Nueva rendición')->assertDontSee('Entregar dinero');
});

it('keeps the easy screens away from workers', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('cash.movement', 'entregar'))->assertForbidden();
    $this->get(route('cashbook.index'))->assertForbidden();
    $this->get(route('workers.index'))->assertForbidden();
});
