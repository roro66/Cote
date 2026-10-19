<?php

use App\Models\Account;
use App\Models\Document;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\PeriodClosing;
use App\Models\Person;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;

/**
 * @return array<int,array<int,mixed>> filas de la primera hoja
 */
function downloaded_xlsx_rows(\Illuminate\Testing\TestResponse $response): array
{
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $response->streamedContent());

    $reader = new Reader();
    $reader->open($path);
    $rows = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
        break;
    }
    $reader->close();
    unlink($path);

    return $rows;
}

function export_worker_with_movements(User $treasurer): array
{
    $treasury = Account::factory()->create(['type' => 'treasury', 'balance' => 0, 'name' => 'Tesorería']);
    $person = Person::factory()->create(['first_name' => 'Ana', 'last_name' => 'Pérez']);
    $worker = Account::factory()->create(['type' => 'person', 'person_id' => $person->id, 'balance' => 0]);

    foreach ([[null, $treasury, 500_000], [$treasury, $worker, 120_000]] as [$from, $to, $amount]) {
        $tx = Transaction::create([
            'transaction_number' => 'TXN-' . uniqid(),
            'type' => $from ? 'transfer' : 'deposit',
            'from_account_id' => $from?->id,
            'to_account_id' => $to->id,
            'amount' => $amount,
            'status' => 'pending',
            'created_by' => $treasurer->id,
            'description' => 'Entrega para materiales',
        ]);
        app(TransactionService::class)->approveTransaction($tx, $treasurer->id);
    }

    return [$treasury, $worker->refresh()];
}

it('exports the account statement in chronological order with numeric amounts', function () {
    $treasurer = create_treasurer_user();
    [, $worker] = export_worker_with_movements($treasurer);

    $response = $this->actingAs($treasurer)->get(route('accounts.movements.export', $worker));

    $response->assertOk()->assertDownload();
    $rows = downloaded_xlsx_rows($response);
    $header = array_search(['Fecha', 'Tipo', 'Referencia', 'Detalle', 'Ingreso', 'Egreso', 'Saldo'], $rows, true);
    expect($header)->not->toBeFalse()
        ->and($rows[$header + 1][1])->toBe('Saldo inicial')
        ->and($rows[$header + 2][0])->toBeInstanceOf(DateTimeInterface::class)
        ->and((float) $rows[$header + 2][4])->toBe(120000.0)
        ->and((float) $rows[$header + 2][6])->toBe(120000.0)
        ->and(end($rows)[3])->toBe('TOTALES');
});

it('only lets management export account statements and closings', function () {
    [, $worker] = export_worker_with_movements(create_treasurer_user());
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('accounts.movements.export', $worker))->assertForbidden();
    $this->actingAs($user)->get(route('closings.export'))->assertForbidden();
});

it('exports a provisional summary for an open month and the saved snapshot for a closed one', function () {
    $treasurer = create_treasurer_user();
    $this->travelTo(Carbon::parse('2026-09-10 10:00'));
    export_worker_with_movements($treasurer);
    $this->travelTo(Carbon::parse('2026-10-05 10:00'));

    $open = $this->actingAs($treasurer)->get(route('closings.export', ['month' => '2026-09']));
    $open->assertOk()->assertDownload('cierre_2026-09_provisorio.xlsx');
    $openRows = collect(downloaded_xlsx_rows($open));
    expect($openRows->firstWhere(0, 'Estado:')[1])->toStartWith('Abierto')
        ->and($openRows->contains(fn ($row) => ($row[0] ?? null) === 'Ana Pérez' && $row[8] === 'Por rendir'))->toBeTrue();

    $this->actingAs($treasurer)->post(route('closings.store'), ['month' => '2026-09', 'treasury_counted' => 380000]);
    expect(PeriodClosing::count())->toBe(1);

    $closed = $this->actingAs($treasurer)->get(route('closings.export', ['month' => '2026-09']));
    $closed->assertOk()->assertDownload('cierre_2026-09.xlsx');
    $closedRows = collect(downloaded_xlsx_rows($closed));
    expect($closedRows->firstWhere(0, 'Estado:')[1])->toStartWith('Cerrado por')
        ->and($closedRows->firstWhere(0, 'Diferencia:')[2])->toBe('Cuadra');
});

it('renders the expense as a pdf including image receipts, only for allowed users', function () {
    Storage::fake(Document::DISK);
    $person = Person::factory()->create();
    $account = Account::factory()->create(['type' => 'person', 'person_id' => $person->id]);
    $expense = Expense::create([
        'expense_number' => 'RND-PDF-1',
        'account_id' => $account->id,
        'submitted_by' => $person->id,
        'total_amount' => 15000,
        'description' => 'Compra de materiales',
        'expense_date' => now()->toDateString(),
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);
    $item = ExpenseItem::create([
        'expense_id' => $expense->id,
        'description' => 'Cemento',
        'amount' => 15000,
        'document_type' => 'boleta',
        'vendor_name' => 'Ferretería',
        'expense_date' => now()->toDateString(),
    ]);
    $path = "expenses/{$expense->id}/items/{$item->id}/boleta.jpg";
    Storage::disk(Document::DISK)->put($path, UploadedFile::fake()->image('boleta.jpg', 3000, 4000)->get());
    Document::create([
        'name' => 'boleta.jpg',
        'file_path' => $path,
        'mime_type' => 'image/jpeg',
        'file_size' => 1000,
        'document_type' => 'boleta',
        'expense_item_id' => $item->id,
        'uploaded_by' => User::factory()->create()->id,
        'is_enabled' => true,
    ]);

    $response = $this->actingAs(create_treasurer_user())->get(route('expenses.pdf', $expense));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $pdf = $response->getContent();
    expect($pdf)->toStartWith('%PDF')
        // La boleta va en su propia página, además de la página del detalle
        ->and(preg_match_all('#/Type\s*/Page[^s]#', $pdf))->toBe(2)
        ->and(substr_count($pdf, '/Subtype /Image'))->toBeGreaterThanOrEqual(1);

    $this->actingAs(User::factory()->create())->get(route('expenses.pdf', $expense))->assertForbidden();
});
