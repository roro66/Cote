<?php

use App\Models\Account;
use App\Models\Document;
use App\Models\Expense;
use App\Models\PeriodClosing;
use App\Models\Person;
use App\Models\Transaction;
use App\Services\AccountStatementService;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Support\Facades\Storage;

it('creates a consistent demo history and removes it without touching real data', function () {
    Storage::fake('local');
    $this->seed(ExpenseCategorySeeder::class);
    create_treasurer_user();
    $treasury = Account::create(['name' => 'Tesorería', 'type' => 'treasury', 'balance' => 50000, 'is_enabled' => true]);
    $real = Person::factory()->create(['first_name' => 'Real', 'is_enabled' => true]);

    $this->artisan('cote:datos-demo', ['--trabajadores' => 6, '--desde' => now()->subMonths(5)->startOfMonth()->toDateString()])
        ->assertSuccessful();

    expect(Person::where('email', 'like', '%@demo.cote.cl')->count())->toBe(6)
        ->and(Transaction::count())->toBeGreaterThan(10)
        ->and(Expense::count())->toBeGreaterThan(10)
        ->and(PeriodClosing::count())->toBe(4)
        ->and((float) $treasury->fresh()->balance)->toBeGreaterThanOrEqual(0.0);

    // Cada saldo se explica por sus movimientos y la caja nunca quedó en negativo
    $statements = app(AccountStatementService::class);
    foreach (Account::all() as $account) {
        $statement = $statements->build($account);
        expect($statement['opening_balance'])->toEqual($account->is($treasury) ? 50000.0 : 0.0);
        if ($account->is($treasury)) {
            expect($statement['movements']->min('balance_after'))->toBeGreaterThanOrEqual(0.0);
        }
    }

    $document = Document::first();
    expect($document)->not->toBeNull();
    Storage::disk('local')->assertExists($document->file_path);
    expect(Expense::where('status', 'approved')->whereNull('reviewed_at')->count())->toBe(0);

    $this->artisan('cote:datos-demo', ['--trabajadores' => 6])->assertFailed();

    $this->artisan('cote:datos-demo', ['--borrar' => true])->assertSuccessful();

    expect(Person::pluck('id')->all())->toBe([$real->id])
        ->and(Transaction::count())->toBe(0)
        ->and(Expense::count())->toBe(0)
        ->and(Document::count())->toBe(0)
        ->and(PeriodClosing::count())->toBe(0)
        ->and((float) $treasury->fresh()->balance)->toBe(50000.0);
    Storage::disk('local')->assertMissing($document->file_path);
});
