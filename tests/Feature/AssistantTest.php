<?php

use App\Livewire\Assistant;
use App\Models\Account;
use App\Models\User;
use App\Services\Ai\AppManual;
use App\Services\Ai\DataAssistant;
use App\Services\Ai\ReadOnlySql;
use App\Services\Ai\Tools\BalancesTool;
use App\Services\Ai\Tools\ExpenseReportsTool;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function assistant_boss(): User
{
    create_role('boss');
    $user = User::factory()->create();
    $user->assignRole('boss');

    return $user;
}

function gemini_reply(array $parts): array
{
    return ['candidates' => [['content' => ['role' => 'model', 'parts' => $parts]]]];
}

beforeEach(function () {
    config(['ai.enabled' => true, 'ai.gemini.api_key' => 'test-key', 'ai.gemini.fallback_models' => [], 'ai.gemini.retry_rounds' => 1]);
});

it('offers workers only their own queries and the manual', function () {
    [$person] = create_person_with_account();
    $this->actingAs(User::factory()->create(['person_id' => $person->id]));

    expect(array_keys(app(DataAssistant::class)->tools()))
        ->toEqualCanonicalizing(['saldos_personas', 'gasto_rendido', 'rendiciones', 'movimientos_cuenta', 'consultar_manual']);
});

it('offers the free SQL query to the boss and the treasurer', function () {
    $this->actingAs(create_treasurer_user());

    expect(app(DataAssistant::class)->tools())->toHaveKeys(['consultar_sql', 'resumen_tesoreria', 'pendientes_aprobacion']);
});

it('limits a worker to their own balance even when asking for someone else', function () {
    [$me, $myAccount] = create_person_with_account(['first_name' => 'Ana', 'last_name' => 'Rojas'], ['balance' => 15000]);
    create_person_with_account(['first_name' => 'Pedro', 'last_name' => 'Soto'], ['balance' => 90000]);
    $this->actingAs(User::factory()->create(['person_id' => $me->id]));

    $result = app(BalancesTool::class)->run(['persona' => 'Pedro Soto']);

    expect($result['filas'])->toHaveCount(1)
        ->and($result['filas'][0]['persona'])->toBe('Ana Rojas')
        ->and($result['filas'][0]['saldo_clp'])->toEqual(15000);
});

it('lets the boss look up anyone by name, accents aside', function () {
    create_person_with_account(['first_name' => 'José', 'last_name' => 'Muñoz'], ['balance' => -8000]);
    $this->actingAs(assistant_boss());

    $result = app(BalancesTool::class)->run(['persona' => 'jose munoz']);

    expect($result['filas'][0]['situacion'])->toBe('Le debemos')
        ->and($result['resumen']['les_debemos_clp'])->toEqual(8000);
});

it('shows a worker the reason their expense report was rejected', function () {
    [$person, $account] = create_person_with_account();
    \App\Models\Expense::create([
        'expense_number' => 'RND-2026-000001', 'account_id' => $account->id, 'submitted_by' => $person->id,
        'total_amount' => 5000, 'description' => 'Colación', 'expense_date' => now()->toDateString(),
        'status' => 'rejected', 'rejection_reason' => 'Falta la boleta',
    ]);
    $this->actingAs(User::factory()->create(['person_id' => $person->id]));

    $result = app(ExpenseReportsTool::class)->run(['estado' => 'rechazada']);

    expect($result['filas'][0]['motivo_rechazo'])->toBe('Falta la boleta');
});

it('rejects SQL that writes or reads private data', function (string $sql) {
    expect(app(ReadOnlySql::class)->rejectReason($sql))->not->toBeNull();
})->with([
    'update' => ["UPDATE accounts SET balance = 0"],
    'two statements' => ["SELECT 1; DELETE FROM accounts"],
    'rut' => ["SELECT rut FROM people"],
    'email' => ["SELECT email FROM users"],
    'bank accounts' => ["SELECT * FROM person_bank_accounts"],
    'people star' => ["SELECT * FROM people"],
    'sessions' => ["SELECT payload FROM sessions"],
]);

it('runs a read only SELECT and returns its rows', function () {
    Account::factory()->create(['type' => 'treasury', 'balance' => 250000]);

    $result = app(ReadOnlySql::class)->run("SELECT SUM(balance) AS tesoreria_clp FROM accounts WHERE type = 'treasury'");

    expect($result['ok'])->toBeTrue()
        ->and($result['filas'][0]['tesoreria_clp'])->toEqual(250000);
});

it('hides boss-only manual sections from a worker', function () {
    $worker = User::factory()->create();

    $result = app(AppManual::class)->search('reabrir un mes cerrado', $worker);

    expect($result['sin_acceso'] ?? false)->toBeTrue();
});

it('gives a worker the steps to create an expense report', function () {
    $result = app(AppManual::class)->search('crear una rendición', User::factory()->create());

    expect($result['ok'])->toBeTrue()
        ->and($result['secciones'][0]['titulo'])->toBe('Crear una rendición');
});

it('declares who can open every manual section', function () {
    $manual = file_get_contents(base_path(AppManual::PATH));
    preg_match_all('/^## (.+)$/m', $manual, $titles);

    foreach (preg_split('/^## /m', $manual) as $index => $section) {
        if ($index === 0) {
            continue;
        }
        expect($section)->toContain('**Quién puede:**');
    }
    expect(count($titles[1]))->toBeGreaterThan(40);
});

it('answers using the manual tool and keeps the steps', function () {
    $this->actingAs(User::factory()->create());
    Http::fake(['*' => Http::sequence()
        ->push(gemini_reply([['functionCall' => ['name' => 'consultar_manual', 'args' => ['tema' => 'crear rendición']]]]))
        ->push(gemini_reply([['text' => 'Ve a **Rendiciones** → Nueva Rendición.']]))]);

    $result = app(DataAssistant::class)->ask('¿Cómo creo una rendición?');

    expect($result['answer'])->toContain('Nueva Rendición')
        ->and($result['steps'][0]['tool'])->toBe('consultar_manual')
        ->and($result['steps'][0]['result']['ok'])->toBeTrue();
});

it('refuses a staff query the model asks for on behalf of a worker', function () {
    $this->actingAs(User::factory()->create());
    Http::fake(['*' => Http::sequence()
        ->push(gemini_reply([['functionCall' => ['name' => 'consultar_sql', 'args' => ['sql' => 'SELECT 1']]]]))
        ->push(gemini_reply([['text' => 'No tengo acceso a eso.']]))]);

    $result = app(DataAssistant::class)->ask('Dame todos los saldos');

    expect($result['steps'][0]['result']['ok'])->toBeFalse();
});

it('shows the assistant page to any signed in user', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('assistant'))
        ->assertOk()
        ->assertSee('¿Cuánto dinero de la empresa tengo?');
});

it('hides the assistant when it is disabled', function () {
    config(['ai.enabled' => false]);

    $this->actingAs(User::factory()->create())->get(route('assistant'))->assertNotFound();
});

it('saves and forgets a question', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Http::fake(['*' => Http::response(gemini_reply([['text' => 'Hola.']]))]);

    $component = Livewire::test(Assistant::class)
        ->set('question', '¿Cuánto tengo por rendir?')
        ->call('ask')
        ->call('toggleSaved', 0);

    expect(\App\Models\AssistantSavedQuestion::where('user_id', $user->id)->count())->toBe(1);

    $component->call('forgetSaved', \App\Models\AssistantSavedQuestion::first()->id);
    expect(\App\Models\AssistantSavedQuestion::count())->toBe(0);
});
