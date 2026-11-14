<?php

use App\Models\Account;
use App\Models\Person;
use App\Models\PersonBankAccount;
use App\Models\User;
use App\Services\AccountStatementService;
use App\Services\PeopleImportService;
use Database\Seeders\AccountTypeSeeder;
use Database\Seeders\BankSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

function import_file(array $rows, string $name = 'nomina.xlsx'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'imp') . '.' . pathinfo($name, PATHINFO_EXTENSION);
    if (str_ends_with($name, '.csv')) {
        file_put_contents($path, implode("\n", array_map(fn ($r) => implode(';', $r), $rows)));
    } else {
        $writer = new Writer();
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();
    }

    return new UploadedFile($path, $name, null, null, true);
}

beforeEach(function () {
    Storage::fake('local');
    $this->seed([BankSeeder::class, AccountTypeSeeder::class]);
    $this->actingAs(create_treasurer_user());
});

it('downloads the template', function () {
    $this->get(route('workers.import.template'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('reviews every row before saving and then imports workers, bank accounts and balances', function () {
    $existing = Person::factory()->create(['first_name' => 'Ana', 'last_name' => 'Antigua', 'rut' => '11111111-1', 'email' => 'ana@old.cl', 'phone' => null]);
    Person::factory()->create(['email' => 'ocupado@correo.cl', 'rut' => '22222222-2']);

    $file = import_file([
        ['Nombres', 'Apellidos', 'RUT', 'Correo', 'Teléfono', 'Dirección', 'Banco', 'Tipo de cuenta', 'N° de cuenta', 'Saldo actual'],
        ['JUAN CARLOS', 'PÉREZ SOTO', '12.345.678-5', 'juan@correo.cl', 56912345678, 'Los Aromos 123', 'Santander', 'Cta Cte', '0-000-12345-6', 45000],
        ['Juan Carlos', 'Pérez Soto', '12345678-5', '', '', '', 'BancoEstado', 'Cuenta RUT', '', ''],
        ['María', 'López', '9.876.543-3', 'ocupado@correo.cl', '', '', 'Banco Inventado', '', '123', '-25.000'],
        ['Pedro', '', '5.126.663-3', '', '', '', '', '', '', ''],
        ['Rosa', 'Díaz', '12.345.678-0', '', '', '', '', '', '', ''],
        ['', '', '11.111.111-1', '', '+56 9 8888 7777', '', '', '', '', ''],
    ]);

    $response = $this->post(route('workers.import.preview'), ['archivo' => $file])->assertOk();
    $analysis = $response->viewData('analysis');

    expect($analysis['summary'])->toBe(['nuevos' => 2, 'actualizar' => 1, 'errores' => 2])
        ->and(Person::count())->toBe(2);

    $rows = collect($analysis['rows'])->keyBy('line');
    expect($rows[2]['first_name'])->toBe('Juan Carlos')
        ->and($rows[2]['last_name'])->toBe('Pérez Soto')
        ->and($rows[2]['phone'])->toBe('56912345678')
        ->and($rows[3]['extra'])->toBeTrue()
        ->and($rows[3]['account_number'])->toBe('12345678')
        ->and($rows[4]['email'])->toBeNull()
        ->and($rows[4]['bank_id'])->toBeNull()
        ->and(implode(' ', $rows[4]['warnings']))->toContain('Banco Inventado')
        ->and($rows[4]['balance'])->toEqual(-25000.0)
        ->and($rows[5]['errors'])->toContain('Falta el nombre o el apellido.')
        ->and($rows[6]['errors'][0])->toContain('no es válido')
        ->and($rows[7]['status'])->toBe('actualizar');
    $response->assertSee('Importar 3 trabajadores');

    $this->post(route('workers.import.store'))->assertRedirect(route('workers.index'));

    $juan = Person::where('rut', '12345678-5')->firstOrFail();
    $juanAccount = Account::where('person_id', $juan->id)->firstOrFail();
    expect($juan->full_name)->toBe('Juan Carlos Pérez Soto')
        ->and($juan->email)->toBe('juan@correo.cl')
        ->and($juan->role_type)->toBe('trabajador')
        ->and((float) $juanAccount->balance)->toBe(45000.0)
        ->and(PersonBankAccount::where('person_id', $juan->id)->count())->toBe(2)
        ->and(PersonBankAccount::where('person_id', $juan->id)->where('is_default', true)->value('account_number'))->toBe('0-000-12345-6')
        ->and($juan->account_number)->toBe('0-000-12345-6');

    $statement = app(AccountStatementService::class)->build($juanAccount);
    expect($statement['opening_balance'])->toEqual(0.0)
        ->and($statement['movements']->first()['detail'])->toContain(PeopleImportService::INITIAL_BALANCE_REASON);

    $maria = Person::where('rut', '9876543-3')->firstOrFail();
    expect($maria->email)->toBeNull()
        ->and((float) Account::where('person_id', $maria->id)->value('balance'))->toBe(-25000.0);

    expect($existing->fresh()->phone)->toBe('+56 9 8888 7777')
        ->and($existing->fresh()->first_name)->toBe('Ana')
        ->and(Account::where('person_id', $existing->id)->exists())->toBeTrue()
        ->and(Storage::disk('local')->allFiles('imports'))->toBe([]);
});

it('reads a semicolon CSV with the treasurer own column names', function () {
    $file = import_file([
        ['Nombre completo', 'Rut', 'Banco', 'N° Cta', 'Por rendir'],
        ['GONZÁLEZ MUÑOZ, PATRICIA', '15.555.555-6', 'bci', '777888999', '$ 120.000'],
        ['Luis Alberto Rojas Díaz', '16666666-K', '', '', ''],
    ], 'planilla.csv');

    $analysis = $this->post(route('workers.import.preview'), ['archivo' => $file])->assertOk()->viewData('analysis');
    $rows = collect($analysis['rows'])->keyBy('line');

    expect($analysis['error'])->toBeNull()
        ->and($rows[2]['first_name'])->toBe('Patricia')
        ->and($rows[2]['last_name'])->toBe('González Muñoz')
        ->and($rows[2]['bank_name'])->toBe('Banco de Crédito e Inversiones')
        ->and($rows[2]['balance'])->toEqual(120000.0)
        ->and($rows[3]['first_name'])->toBe('Luis Alberto')
        ->and($rows[3]['last_name'])->toBe('Rojas Díaz')
        ->and($rows[3]['status'])->toBe('nuevo');
});

it('reads a CSV saved by Excel on Windows (Windows-1252)', function () {
    $file = import_file([['Nombres;Apellidos;RUT'], ['Iñaki;Muñoz;16666666-K']], 'windows.csv');
    file_put_contents($file->getRealPath(), mb_convert_encoding(file_get_contents($file->getRealPath()), 'Windows-1252', 'UTF-8'));

    $analysis = $this->post(route('workers.import.preview'), ['archivo' => $file])->assertOk()->viewData('analysis');

    expect($analysis['rows'][0]['first_name'])->toBe('Iñaki')
        ->and($analysis['rows'][0]['last_name'])->toBe('Muñoz');
});

it('explains when the RUT column is missing', function () {
    $file = import_file([['Nombre', 'Cargo'], ['Juan', 'Maestro']]);

    $this->post(route('workers.import.preview'), ['archivo' => $file])
        ->assertOk()
        ->assertSee('No encontré la columna del RUT');
});

it('keeps workers out of the import screen', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('workers.import'))->assertForbidden();
});
