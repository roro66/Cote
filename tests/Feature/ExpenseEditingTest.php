<?php

use App\Models\Account;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Person;
use App\Services\ExpenseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake(Document::DISK));

function item_payload(array $overrides = []): array
{
    return array_merge([
        'expense_date' => now()->subDays(2)->toDateString(),
        'description' => 'Almuerzo',
        'amount' => 12_000,
        'document_type' => 'boleta',
        'vendor_name' => 'Restorán',
    ], $overrides);
}

function create_expense_via_http($test, array $items): Expense
{
    $person = Person::factory()->create();
    $account = Account::factory()->create(['type' => 'person', 'person_id' => $person->id]);

    $test->actingAs(create_treasurer_user())
        ->postJson(route('expenses.store'), ['account_id' => $account->id, 'description' => 'Semana', 'items' => $items])
        ->assertOk();

    return Expense::latest('id')->first();
}

it('stores the receipt date of each item and dates the expense by the latest one', function () {
    $expense = create_expense_via_http($this, [
        item_payload(['expense_date' => '2026-10-01']),
        item_payload(['expense_date' => '2026-10-05', 'vendor_name' => 'Ferretería']),
    ]);

    expect($expense->items->pluck('expense_date')->map->format('Y-m-d')->sort()->values()->all())->toBe(['2026-10-01', '2026-10-05']);
    expect($expense->expense_date->format('Y-m-d'))->toBe('2026-10-05');
    expect((float) $expense->total_amount)->toBe(24000.0);
});

it('rejects receipts dated in the future', function () {
    $person = Person::factory()->create();
    $account = Account::factory()->create(['type' => 'person', 'person_id' => $person->id]);

    $this->actingAs(create_treasurer_user())
        ->postJson(route('expenses.store'), [
            'account_id' => $account->id,
            'description' => 'Semana',
            'items' => [item_payload(['expense_date' => now()->addDay()->toDateString()])],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items.0.expense_date');
});

it('keeps existing receipts when editing and removes only the ones marked', function () {
    $expense = create_expense_via_http($this, [item_payload([
        'files' => [UploadedFile::fake()->image('boleta1.jpg'), UploadedFile::fake()->image('boleta2.jpg')],
    ])]);
    $item = $expense->items()->first();
    [$keep, $remove] = $item->documents()->orderBy('id')->get()->all();

    $this->actingAs(create_treasurer_user())
        ->postJson(route('expenses.update', $expense), [
            '_method' => 'PUT',
            'account_id' => $expense->account_id,
            'description' => 'Semana corregida',
            'items' => [
                item_payload(['id' => $item->id, 'amount' => 15_000, 'remove_documents' => [$remove->id], 'files' => [UploadedFile::fake()->image('boleta3.jpg')]]),
            ],
        ])
        ->assertOk();

    $names = $item->refresh()->documents()->pluck('name')->sort()->values()->all();
    expect($names)->toBe(['boleta1.jpg', 'boleta3.jpg']);
    Storage::disk(Document::DISK)->assertExists($keep->file_path);
    Storage::disk(Document::DISK)->assertMissing($remove->file_path);
    expect($expense->refresh()->items()->count())->toBe(1);
    expect((float) $expense->total_amount)->toBe(15000.0);
});

it('deletes items left out of the edit together with their receipts', function () {
    $expense = create_expense_via_http($this, [
        item_payload(['files' => [UploadedFile::fake()->image('a.jpg')]]),
        item_payload(['vendor_name' => 'Ferretería', 'files' => [UploadedFile::fake()->image('b.jpg')]]),
    ]);
    [$first, $second] = $expense->items()->orderBy('id')->get()->all();
    $orphan = $second->documents()->first();

    $this->actingAs(create_treasurer_user())
        ->postJson(route('expenses.update', $expense), [
            '_method' => 'PUT',
            'account_id' => $expense->account_id,
            'description' => 'Semana',
            'items' => [item_payload(['id' => $first->id])],
        ])
        ->assertOk();

    expect($expense->items()->pluck('id')->all())->toBe([$first->id]);
    Storage::disk(Document::DISK)->assertMissing($orphan->file_path);
    expect(Document::find($orphan->id))->toBeNull();
});

it('lets a receipt from a rejected expense be submitted again', function () {
    $receipt = ['receipt_number' => 'B-555', 'vendor_name' => 'Copec'];
    $rejected = create_expense_via_http($this, [item_payload($receipt)]);
    app(ExpenseService::class)->rejectExpense($rejected, create_treasurer_user()->id, 'Monto ilegible');

    $this->actingAs(create_treasurer_user())
        ->postJson(route('expenses.store'), ['account_id' => $rejected->account_id, 'description' => 'Rehecha', 'items' => [item_payload($receipt)]])
        ->assertOk();

    $this->actingAs(create_treasurer_user())
        ->postJson(route('expenses.store'), ['account_id' => $rejected->account_id, 'description' => 'Duplicada', 'items' => [item_payload($receipt)]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items.0.receipt_number');
});
