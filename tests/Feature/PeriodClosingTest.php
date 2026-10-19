<?php

use App\Models\Account;
use App\Models\Expense;
use App\Models\PeriodClosing;
use App\Models\Person;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\PeriodReportService;
use App\Services\TransactionService;
use Illuminate\Support\Carbon;

function approved_tx(string $at, ?Account $from, Account $to, int $amount, User $user): void
{
    test()->travelTo(Carbon::parse($at));
    $tx = Transaction::create([
        'transaction_number' => 'TXN-' . uniqid(),
        'type' => $from ? 'transfer' : 'deposit',
        'from_account_id' => $from?->id,
        'to_account_id' => $to->id,
        'amount' => $amount,
        'status' => 'pending',
        'created_by' => $user->id,
        'description' => 'Movimiento',
    ]);
    app(TransactionService::class)->approveTransaction($tx, $user->id);
}

function approved_expense(string $at, Account $account, int $amount, User $user): void
{
    test()->travelTo(Carbon::parse($at));
    $expense = Expense::create([
        'expense_number' => 'RND-' . uniqid(),
        'account_id' => $account->id,
        'submitted_by' => $account->person_id,
        'total_amount' => $amount,
        'description' => 'Rendición',
        'expense_date' => now()->toDateString(),
        'status' => 'submitted',
    ]);
    app(ExpenseService::class)->approveExpense($expense, $user->id);
}

function petty_cash_history(): array
{
    $user = create_treasurer_user();
    $treasury = Account::factory()->create(['type' => 'treasury', 'balance' => 0, 'name' => 'Tesorería']);
    $person = Person::factory()->create();
    $worker = Account::factory()->create(['type' => 'person', 'person_id' => $person->id, 'balance' => 0]);

    approved_tx('2026-09-15 10:00', null, $treasury, 1_000_000, $user);
    approved_tx('2026-09-16 10:00', $treasury, $worker, 200_000, $user);
    approved_expense('2026-10-05 10:00', $worker, 50_000, $user);
    approved_tx('2026-10-06 10:00', $worker, $treasury, 30_000, $user);
    approved_tx('2026-11-02 10:00', $treasury, $worker, 10_000, $user);
    test()->travelTo(Carbon::parse('2026-11-03 09:00'));

    return [$treasury, $worker, $user];
}

it('summarizes a month per account using approval dates', function () {
    [$treasury, $worker] = petty_cash_history();

    $report = app(PeriodReportService::class)->build(Carbon::parse('2026-10-01'));
    $rows = collect($report['rows'])->keyBy('account_id');

    expect($rows[$treasury->id])->toMatchArray(['opening' => 800000.0, 'in' => 30000.0, 'out' => 0.0, 'closing' => 830000.0]);
    expect($rows[$worker->id])->toMatchArray(['opening' => 200000.0, 'out' => 30000.0, 'rendered' => 50000.0, 'closing' => 120000.0]);
    expect($report['totals'])->toMatchArray(['treasury_closing' => 830000.0, 'to_render' => 120000.0, 'rendered' => 50000.0]);

    $september = app(PeriodReportService::class)->build(Carbon::parse('2026-09-01'));
    expect($september['totals']['deposits'])->toBe(1000000.0);
});

it('closes a finished month with a frozen snapshot and the cash count', function () {
    [$treasury] = petty_cash_history();
    $user = create_treasurer_user();

    $this->actingAs($user)->post(route('closings.store'), ['month' => '2026-09'])->assertRedirect();
    $this->actingAs($user)
        ->post(route('closings.store'), ['month' => '2026-10', 'treasury_counted' => 829_000, 'notes' => 'Faltan $1.000'])
        ->assertRedirect(route('closings.index', ['month' => '2026-10']));

    $closing = PeriodClosing::whereDate('period', '2026-10-01')->sole();
    expect((float) $closing->snapshot['totals']['treasury_closing'])->toBe(830000.0);
    expect((float) $closing->treasury_counted)->toBe(829000.0);

    $treasury->update(['balance' => 1]);
    $this->actingAs($user)->get(route('closings.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertSee('Cerrado')
        ->assertSee('Diferencia: -$1.000')
        ->assertSee('$830.000');
});

it('only closes finished months, in order and once', function () {
    petty_cash_history();
    $user = create_treasurer_user();

    $this->actingAs($user)->post(route('closings.store'), ['month' => '2026-11']);
    expect(PeriodClosing::count())->toBe(0);

    $this->actingAs($user)->post(route('closings.store'), ['month' => '2026-10']);
    $this->actingAs($user)->post(route('closings.store'), ['month' => '2026-10']);
    $this->actingAs($user)->post(route('closings.store'), ['month' => '2026-09']);
    expect(PeriodClosing::pluck('period')->map->format('Y-m')->all())->toBe(['2026-10']);
});

it('lets only the boss reopen the latest closed month', function () {
    petty_cash_history();
    $treasurer = create_treasurer_user();
    $this->actingAs($treasurer)->post(route('closings.store'), ['month' => '2026-09']);
    $this->actingAs($treasurer)->post(route('closings.store'), ['month' => '2026-10']);
    [$september, $october] = PeriodClosing::orderBy('period')->get()->all();

    $this->actingAs($treasurer)->delete(route('closings.destroy', $october))->assertForbidden();

    create_role('boss');
    $boss = User::factory()->create();
    $boss->assignRole('boss');
    $this->actingAs($boss)->delete(route('closings.destroy', $september));
    expect(PeriodClosing::count())->toBe(2);

    $this->actingAs($boss)->delete(route('closings.destroy', $october))->assertRedirect();
    expect(PeriodClosing::pluck('id')->all())->toBe([$september->id]);
});
