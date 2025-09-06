<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ExpenseCategory;
use App\Models\Expense;
use App\Models\Account;
use App\Models\User;

uses(RefreshDatabase::class);

it('stores expense with items including expense_category_id', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $person = \App\Models\Person::create([
        'first_name' => 'Persona',
        'last_name' => 'Prueba',
        'rut' => '99999999-9',
        'role_type' => 'trabajador',
        'is_enabled' => true,
    ]);
    $account = Account::factory()->create(['person_id' => $person->id, 'type' => 'person']);
    $category = ExpenseCategory::factory()->create();

    $payload = [
        'account_id' => $account->id,
        'description' => 'Compra de prueba',
        'currency' => 'CLP',
        'items' => [
            [
                'description' => 'Item 1',
                'amount' => 1000,
                'currency' => 'CLP',
                'document_type' => 'boleta',
                'vendor_name' => 'Proveedor X',
                'receipt_number' => 'R001',
                'expense_category_id' => $category->id
            ],
        ],
    ];

    $this->post(route('expenses.store'), $payload)
         ->assertRedirect();

    $this->assertDatabaseHas('expenses', ['description' => 'Compra de prueba']);
    $this->assertDatabaseHas('expense_items', [
        'description' => 'Item 1',
        'expense_category_id' => $category->id,
    ]);
});
