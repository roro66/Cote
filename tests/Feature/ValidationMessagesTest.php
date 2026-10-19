<?php

use App\Models\Account;
use App\Models\Bank;
use App\Models\Expense;
use App\Models\Person;
use App\Models\Transaction;
use App\Models\User;

// Cada formulario debe responder sus errores en español y con nombres de campo legibles.
it('returns validation messages in spanish with readable field names', function () {
    create_role('boss');
    $boss = User::factory()->create();
    $boss->assignRole('boss');
    $person = Person::factory()->create();
    $account = Account::factory()->create(['type' => 'person', 'person_id' => $person->id]);
    $treasury = Account::factory()->create(['type' => 'treasury']);
    $tx = Transaction::create(['transaction_number' => 'T1', 'type' => 'transfer', 'from_account_id' => $treasury->id, 'to_account_id' => $account->id, 'amount' => 1, 'status' => 'pending', 'created_by' => $boss->id, 'description' => 'x']);
    $expense = Expense::create(['expense_number' => 'R1', 'account_id' => $account->id, 'submitted_by' => $person->id, 'total_amount' => 1, 'description' => 'x', 'expense_date' => now(), 'status' => 'submitted']);

    $cases = [
        ['POST', route('people.store'), []],
        ['POST', route('people.store'), ['first_name' => 'a', 'last_name' => 'b', 'rut' => '1-1', 'email' => 'no-es-correo', 'role_type' => 'x', 'phone' => str_repeat('9', 30)]],
        ['POST', route('accounts.store'), ['type' => 'otro']],
        ['PUT', route('accounts.update', $account), ['balance' => 'abc']],
        ['POST', route('transactions.store'), []],
        ['POST', route('transactions.store'), ['type' => 'transfer', 'from_account_id' => $treasury->id, 'to_account_id' => $treasury->id, 'amount' => 0, 'description' => '']],
        ['POST', route('transactions.store'), ['type' => 'deposit', 'from_account_id' => $treasury->id, 'to_account_id' => $treasury->id, 'amount' => 5, 'description' => 'x']],
        ['POST', route('expenses.store'), []],
        ['POST', route('expenses.store'), ['account_id' => $account->id, 'description' => 'x', 'items' => [['expense_date' => now()->addDays(3)->toDateString(), 'amount' => 0, 'document_type' => 'x']]]],
        ['POST', route('approvals.transactions.reject', $tx), []],
        ['POST', route('approvals.expenses.reject', $expense), []],
        ['POST', route('users.store'), []],
        ['POST', route('users.store'), ['name' => 'a', 'email' => 'x', 'password' => '1', 'password_confirmation' => '2', 'person_id' => 999, 'roles' => ['nope']]],
        ['POST', route('banks.store'), []],
        ['POST', route('expense-categories.store'), []],
        ['POST', route('account-types.store'), []],
        ['POST', route('people.personal-accounts.store', $person), []],
        ['POST', route('reports.monthly-expenses'), ['start_date' => '2026-10-10', 'end_date' => '2026-10-01']],
        ['POST', route('closings.store'), ['month' => 'octubre', 'treasury_counted' => 'abc']],
        ['PATCH', route('profile.update'), ['name' => '', 'email' => 'x']],
        ['PUT', route('password.update'), ['current_password' => 'mala', 'password' => '1', 'password_confirmation' => '2']],
    ];

    $problems = [];
    foreach ($cases as [$method, $url, $data]) {
        $res = $this->actingAs($boss)->json($method, $url, $data);
        $path = $method . ' ' . parse_url($url, PHP_URL_PATH);
        if ($res->status() !== 422) {
            $problems[] = "{$path}: se esperaba 422 y respondió {$res->status()}";
            continue;
        }
        $errors = $res->json('errors') ?? [];
        $messages = array_merge([$res->json('message')], ...array_values($errors));
        foreach ($messages as $message) {
            // Resumen o reglas sin traducir
            if (preg_match('/_|\bthe\b|\bfield\b|\bmore errors?\b|\btoday\b|\bmust\b|validation\./i', $message)) {
                $problems[] = "{$path}: {$message}";
            }
        }
        // Sin traducción, Laravel muestra el nombre técnico con espacios ("rejection reason", "items.0.amount")
        foreach ($errors as $field => $fieldMessages) {
            $technical = str_replace('_', ' ', $field);
            if (in_array($field, ['rut', 'alias', 'roles'], true)) {
                continue; // se escriben igual en español
            }
            foreach ($fieldMessages as $message) {
                if (str_contains($message, $technical)) {
                    $problems[] = "{$path}: {$message}";
                }
            }
        }
    }

    expect($problems)->toBe([]);
});
