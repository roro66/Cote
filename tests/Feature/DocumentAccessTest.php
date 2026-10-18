<?php

use App\Models\Account;
use App\Models\Document;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function document_for_person(Person $person, User $uploader): Document
{
    $account = Account::factory()->create(['type' => 'person', 'person_id' => $person->id]);
    $expense = Expense::create([
        'expense_number' => 'RND-TEST-' . uniqid(),
        'account_id' => $account->id,
        'submitted_by' => $person->id,
        'total_amount' => 1000,
        'description' => 'Rendición de prueba',
        'expense_date' => now()->toDateString(),
        'status' => 'submitted',
    ]);
    $item = ExpenseItem::create([
        'expense_id' => $expense->id,
        'description' => 'Ítem',
        'amount' => 1000,
        'document_type' => 'boleta',
        'vendor_name' => 'Proveedor',
        'expense_date' => now()->toDateString(),
    ]);

    $path = "expenses/{$expense->id}/items/{$item->id}/boleta.pdf";
    Storage::disk(Document::DISK)->put($path, '%PDF-1.4 prueba');

    return Document::create([
        'name' => 'boleta.pdf',
        'file_path' => $path,
        'mime_type' => 'application/pdf',
        'file_size' => 15,
        'document_type' => 'boleta',
        'expense_item_id' => $item->id,
        'uploaded_by' => $uploader->id,
        'is_enabled' => true,
    ]);
}

beforeEach(function () {
    Storage::fake(Document::DISK);
    Storage::fake('public');
});

it('stores uploaded receipts outside the public disk', function () {
    $person = Person::factory()->create();
    $account = Account::factory()->create(['type' => 'person', 'person_id' => $person->id]);

    $this->actingAs(User::factory()->create())->post(route('expenses.store'), [
        'account_id' => $account->id,
        'description' => 'Compra con boleta',
        'currency' => 'CLP',
        'items' => [[
            'description' => 'Ítem',
            'amount' => 1000,
            'currency' => 'CLP',
            'document_type' => 'boleta',
            'vendor_name' => 'Proveedor',
            'receipt_number' => 'B-1',
            'files' => [\Illuminate\Http\UploadedFile::fake()->create('boleta.pdf', 10, 'application/pdf')],
        ]],
    ]);

    $doc = Document::sole();
    Storage::disk(Document::DISK)->assertExists($doc->file_path);
    Storage::disk('public')->assertMissing($doc->file_path);
});

it('moves existing receipts from the public disk to the private disk', function () {
    $doc = document_for_person(Person::factory()->create(), User::factory()->create());
    Storage::disk(Document::DISK)->delete($doc->file_path);
    Storage::disk('public')->put($doc->file_path, 'legacy');

    $migration = require database_path('migrations/2026_10_18_200000_move_documents_to_private_disk.php');
    $migration->up();
    $migration->up();

    Storage::disk('public')->assertMissing($doc->file_path);
    expect(Storage::disk(Document::DISK)->get($doc->file_path))->toBe('legacy');
});

it('requires login to download a receipt', function () {
    $doc = document_for_person(Person::factory()->create(), User::factory()->create());

    $this->get(route('documents.show', $doc))->assertRedirect(route('login'));
});

it('forbids unrelated users from downloading a receipt', function () {
    $doc = document_for_person(Person::factory()->create(), User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->get(route('documents.show', $doc))
        ->assertForbidden();
});

it('lets the treasurer, the uploader and the submitter download a receipt', function () {
    $person = Person::factory()->create();
    $uploader = User::factory()->create();
    $doc = document_for_person($person, $uploader);
    $submitter = User::factory()->create(['person_id' => $person->id]);

    foreach ([create_treasurer_user(), $uploader, $submitter] as $user) {
        $this->actingAs($user)
            ->get(route('documents.show', $doc))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
});
