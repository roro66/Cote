<?php

use App\Livewire\ExpenseIntake;
use App\Models\Account;
use App\Models\Document;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Person;
use App\Models\User;
use App\Services\ExpenseService;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function intake_worker(int $balance = 100000): Account
{
    $person = Person::factory()->create(['first_name' => 'Jorge', 'last_name' => 'Soto Vera', 'is_enabled' => true]);

    return Account::create(['name' => 'Jorge Soto Vera', 'type' => 'person', 'person_id' => $person->id, 'balance' => $balance, 'is_enabled' => true]);
}

function intake_row(array $overrides = []): array
{
    return array_merge([
        'date' => now()->subDay()->toDateString(),
        'category_id' => (string) ExpenseCategory::where('name', 'Peaje')->value('id'),
        'project' => 'Lago Chapo',
        'city' => 'Puerto Montt',
        'type' => 'boleta',
        'vendor' => 'Ruta 5 Sur',
        'number' => (string) random_int(100000, 999999),
        'amount' => '900',
    ], $overrides);
}

beforeEach(function () {
    Storage::fake('local');
    $this->seed(ExpenseCategorySeeder::class);
    $this->treasurer = create_treasurer_user();
    $this->actingAs($this->treasurer);
});

it('registers a paper expense report as approved with its photos, project and city', function () {
    $account = intake_worker(100000);
    $food = (string) ExpenseCategory::where('name', 'Alimentación')->value('id');

    Livewire::test(ExpenseIntake::class)
        ->call('choose', $account->id)
        ->assertSet('step', 'planilla')
        ->set('newPhotos', [UploadedFile::fake()->image('planilla.jpg'), UploadedFile::fake()->image('boletas.jpg')])
        ->set('rows', [
            intake_row(['amount' => '900']),
            intake_row(['category_id' => $food, 'type' => 'factura', 'vendor' => 'Ferretería Oriente', 'number' => '1234', 'amount' => '17400']),
            intake_row(['category_id' => $food, 'vendor' => 'Getnet', 'number' => '5555', 'amount' => '46810']),
        ])
        ->set('declaredTotal', '65110')
        ->assertSee('¡Cuadra con la planilla!')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('step', 'listo')
        ->assertSee('quedó registrada y aprobada')
        ->assertSee('Ahora Jorge tiene $34.890 en su poder');

    $expense = Expense::with('items', 'photos')->sole();
    expect($expense->status)->toBe('approved')
        ->and($expense->isReceived())->toBeTrue()
        ->and((int) $expense->total_amount)->toBe(65110)
        ->and((int) $expense->declared_total)->toBe(65110)
        ->and($expense->registered_by)->toBe($this->treasurer->id)
        ->and($expense->reviewed_by)->toBe($this->treasurer->id)
        ->and($expense->photos)->toHaveCount(2)
        ->and($expense->items->pluck('project')->unique()->all())->toBe(['Lago Chapo'])
        ->and($expense->items->pluck('city')->unique()->all())->toBe(['Puerto Montt'])
        ->and($expense->items->firstWhere('vendor_name', 'Ferretería Oriente')->description)->toBe('Alimentación')
        ->and((float) $account->fresh()->balance)->toBe(34890.0);
    Storage::disk('local')->assertExists($expense->photos->first()->file_path);

    $this->get(route('expenses.show', $expense))
        ->assertOk()
        ->assertSee('Fotos de la rendición (2)')
        ->assertSee('Lago Chapo')
        ->assertSee('Cuadra');
    $this->get(route('expenses.pdf', $expense))->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('copies date, project, city and document type into a new line', function () {
    $account = intake_worker();

    $component = Livewire::test(ExpenseIntake::class)
        ->call('choose', $account->id)
        ->set('rows.0.project', 'Lago Chapo')
        ->set('rows.0.city', 'Puerto Varas')
        ->set('rows.0.type', 'factura')
        ->set('rows.0.date', now()->subDays(2)->toDateString())
        ->call('addRow');

    expect($component->get('rows.1'))->toMatchArray([
        'project' => 'Lago Chapo', 'city' => 'Puerto Varas', 'type' => 'factura', 'date' => now()->subDays(2)->toDateString(),
        'vendor' => '', 'number' => '', 'amount' => '',
    ]);
});

it('asks for the photos, the vendor and the document number', function () {
    $account = intake_worker();

    Livewire::test(ExpenseIntake::class)
        ->call('choose', $account->id)
        ->set('rows', [intake_row(['vendor' => '', 'number' => ''])])
        ->call('save')
        ->assertHasErrors(['rows.0.vendor', 'rows.0.number']);

    Livewire::test(ExpenseIntake::class)
        ->call('choose', $account->id)
        ->set('rows', [intake_row()])
        ->call('save')
        ->assertHasErrors(['photos']);

    expect(Expense::count())->toBe(0);
});

it('detects a receipt already rendered in another expense report', function () {
    $account = intake_worker();
    $row = intake_row(['vendor' => 'Copec', 'number' => '777']);

    Livewire::test(ExpenseIntake::class)
        ->call('choose', $account->id)
        ->set('newPhotos', [UploadedFile::fake()->image('a.jpg')])
        ->set('rows', [$row])
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(ExpenseIntake::class)
        ->call('choose', $account->id)
        ->set('newPhotos', [UploadedFile::fake()->image('b.jpg')])
        ->set('rows', [array_merge($row, ['vendor' => ' copec '])])
        ->call('save')
        ->assertHasErrors(['rows.0.number']);

    expect(Expense::count())->toBe(1);
});

it('corrects an annulled paper expense report and approves it again', function () {
    $account = intake_worker(50000);

    Livewire::test(ExpenseIntake::class)
        ->call('choose', $account->id)
        ->set('newPhotos', [UploadedFile::fake()->image('planilla.jpg')])
        ->set('rows', [intake_row(['amount' => '20000'])])
        ->call('save');
    $expense = Expense::sole();
    expect((float) $account->fresh()->balance)->toBe(30000.0);

    app(ExpenseService::class)->annulExpense($expense, $this->treasurer->id, 'Monto mal copiado');
    expect((float) $account->fresh()->balance)->toBe(50000.0);

    $this->get(route('expenses.edit', $expense))->assertRedirect(route('expenses.intake.edit', $expense));

    Livewire::test(ExpenseIntake::class, ['expense' => $expense])
        ->assertSet('step', 'planilla')
        ->assertSet('rows.0.amount', '20000')
        ->set('rows.0.amount', '2000')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('step', 'listo');

    $expense->refresh();
    expect($expense->status)->toBe('approved')
        ->and((int) $expense->total_amount)->toBe(2000)
        ->and($expense->photos()->count())->toBe(1)
        ->and((float) $account->fresh()->balance)->toBe(48000.0);
});

it('keeps paper expense reports for staff only', function () {
    $worker = User::factory()->create();
    $this->actingAs($worker);

    $this->get(route('expenses.intake'))->assertForbidden();
});

it('offers Registrar rendición in the top bar and hides the review button when nothing is pending', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Registrar rendición')
        ->assertDontSee('aria-label="Revisar rendiciones"', false);
});

it('lets the expense photos be viewed only by staff and the owner', function () {
    $account = intake_worker();
    Livewire::test(ExpenseIntake::class)
        ->call('choose', $account->id)
        ->set('newPhotos', [UploadedFile::fake()->image('planilla.jpg')])
        ->set('rows', [intake_row()])
        ->call('save');
    $photo = Document::whereNotNull('expense_id')->sole();

    $this->get(route('documents.show', $photo))->assertOk();

    $owner = User::factory()->create(['person_id' => $account->person_id]);
    $this->actingAs($owner)->get(route('documents.show', $photo))->assertOk();

    $this->actingAs(User::factory()->create())->get(route('documents.show', $photo))->assertForbidden();
});
