<?php

use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Person;
use App\Services\ExpenseService;

function expense_with_item(string $status, int $amount = 30_000): array
{
    $person = Person::factory()->create();
    $account = Account::factory()->create(['type' => 'person', 'person_id' => $person->id, 'balance' => 100_000]);
    $expense = Expense::create([
        'expense_number' => 'RND-' . uniqid(),
        'account_id' => $account->id,
        'submitted_by' => $person->id,
        'total_amount' => $amount,
        'description' => 'Rendición de prueba',
        'expense_date' => now()->toDateString(),
        'status' => 'submitted',
    ]);
    ExpenseItem::create([
        'expense_id' => $expense->id,
        'description' => 'Almuerzo',
        'amount' => $amount,
        'document_type' => 'boleta',
        'vendor_name' => 'Restorán',
        'expense_date' => now()->toDateString(),
    ]);

    $service = app(ExpenseService::class);
    if ($status === 'approved') {
        $service->approveExpense($expense, create_treasurer_user()->id);
    } elseif ($status === 'rejected') {
        $service->rejectExpense($expense, create_treasurer_user()->id, 'Falta la boleta');
    }

    return [$expense->refresh(), $account->refresh()];
}

function expense_update_payload(Expense $expense, int $amount): array
{
    return [
        'account_id' => $expense->account_id,
        'description' => 'Rendición corregida',
        'currency' => 'CLP',
        'items' => [[
            'description' => 'Almuerzo',
            'expense_date' => now()->toDateString(),
            'amount' => $amount,
            'currency' => 'CLP',
            'document_type' => 'boleta',
            'vendor_name' => 'Restorán',
        ]],
    ];
}

it('does not allow editing an approved expense', function () {
    [$expense, $account] = expense_with_item('approved');
    $treasurer = create_treasurer_user();

    $this->actingAs($treasurer)->get(route('expenses.edit', $expense))->assertForbidden();
    $this->actingAs($treasurer)
        ->put(route('expenses.update', $expense), expense_update_payload($expense, 999_000))
        ->assertForbidden();

    expect((float) $expense->refresh()->total_amount)->toBe(30000.0);
    expect((float) $account->refresh()->balance)->toBe(70000.0);
});

it('does not allow deleting an approved expense', function () {
    [$expense, $account] = expense_with_item('approved');

    $this->actingAs(create_treasurer_user())
        ->delete(route('expenses.destroy', $expense))
        ->assertForbidden();

    expect($expense->refresh()->is_enabled)->toBeTrue();
    expect((float) $account->refresh()->balance)->toBe(70000.0);
});

it('resubmits a rejected expense when it is corrected', function () {
    [$expense] = expense_with_item('rejected');

    $this->actingAs(create_treasurer_user())
        ->put(route('expenses.update', $expense), expense_update_payload($expense, 25_000))
        ->assertRedirect();

    $expense->refresh();
    expect($expense->status)->toBe('submitted');
    expect($expense->rejection_reason)->toBeNull();
    expect((float) $expense->total_amount)->toBe(25000.0);
});

it('still allows editing and deleting a submitted expense', function () {
    [$expense] = expense_with_item('submitted');
    $treasurer = create_treasurer_user();

    $this->actingAs($treasurer)->get(route('expenses.edit', $expense))->assertOk();
    $this->actingAs($treasurer)->delete(route('expenses.destroy', $expense))->assertRedirect();

    expect($expense->refresh()->is_enabled)->toBeFalse();
});
