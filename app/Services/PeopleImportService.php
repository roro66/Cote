<?php

namespace App\Services;

use App\Helpers\RutHelper;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Bank;
use App\Models\Expense;
use App\Models\Person;
use App\Models\PersonBankAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ODS\Reader as OdsReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Importa la nómina de trabajadores desde la planilla del tesorero. Primero revisa cada fila
 * sin guardar nada; luego importa solo las filas sin problemas. El RUT identifica a la persona:
 * si ya existe se actualiza, y cada fila con el mismo RUT agrega otra cuenta bancaria.
 */
class PeopleImportService
{
    public const INITIAL_BALANCE_REASON = 'Saldo inicial importado desde Excel';

    /** Título en la planilla modelo (null si no va en ella) y otros nombres con que se reconoce la columna. */
    public const COLUMNS = [
        'nombres' => ['Nombres', ['nombre', 'nombres', 'primer nombre', 'nombres del trabajador']],
        'apellidos' => ['Apellidos', ['apellido', 'apellidos']],
        'apellido_paterno' => [null, ['apellido paterno', 'ap paterno', 'paterno', 'primer apellido']],
        'apellido_materno' => [null, ['apellido materno', 'ap materno', 'materno', 'segundo apellido']],
        'nombre_completo' => [null, ['nombre completo', 'nombre y apellido', 'nombres y apellidos', 'trabajador', 'funcionario', 'colaborador', 'empleado', 'nombre trabajador', 'nombre del trabajador']],
        'rut' => ['RUT', ['rut', 'run', 'rut trabajador', 'rut del trabajador', 'cedula', 'cedula de identidad']],
        'correo' => ['Correo', ['correo', 'email', 'e mail', 'mail', 'correo electronico']],
        'telefono' => ['Teléfono', ['telefono', 'fono', 'celular', 'movil', 'whatsapp', 'telefono celular']],
        'direccion' => ['Dirección', ['direccion', 'domicilio']],
        'banco' => ['Banco', ['banco', 'institucion', 'institucion bancaria', 'banco destino']],
        'tipo_cuenta' => ['Tipo de cuenta', ['tipo de cuenta', 'tipo cuenta', 'tipo cta', 'tipo']],
        'numero_cuenta' => ['N° de cuenta', ['n de cuenta', 'numero de cuenta', 'n cuenta', 'no cuenta', 'nro cuenta', 'nro de cuenta', 'cuenta', 'cta', 'n cta', 'numero cta', 'cuenta bancaria', 'numero cuenta']],
        'saldo' => ['Saldo actual', ['saldo', 'saldo actual', 'saldo inicial', 'por rendir', 'saldo por rendir', 'monto pendiente']],
    ];

    /** Formas habituales de escribir cada banco → nombre en COTE. */
    private const BANK_ALIASES = [
        'banco del estado de chile' => ['estado', 'banco estado', 'bancoestado', 'banco del estado', 'bech', 'cuenta rut'],
        'banco de chile' => ['chile', 'banco chile', 'edwards', 'banco edwards', 'citibank', 'banco de chile edwards'],
        'banco de credito e inversiones' => ['bci', 'banco bci', 'credito e inversiones'],
        'banco santander chile' => ['santander', 'banco santander'],
        'scotiabank chile' => ['scotiabank', 'scotia', 'banco scotiabank'],
        'banco itau chile' => ['itau', 'banco itau', 'itau corpbanca'],
        'tenpocard' => ['tenpo'],
        'mach bci' => ['mach'],
    ];

    /** @var array<string, int>|null */
    private ?array $banks = null;

    /** @var array<string, int>|null */
    private ?array $accountTypes = null;

    /**
     * @return array{error: ?string, columns: array<string, string>, ignored: list<string>, rows: list<array<string, mixed>>, summary: array<string, int>}
     */
    public function analyze(string $path, string $extension): array
    {
        $sheet = $this->readSheet($path, $extension);
        [$headerIndex, $columns, $ignored] = $this->findHeader($sheet);

        if ($headerIndex === null) {
            return [
                'error' => 'No encontré la columna del RUT. La primera fila con títulos debe tener una columna llamada «RUT». Puedes usar la planilla modelo.',
                'columns' => [], 'ignored' => [], 'rows' => [], 'summary' => ['nuevos' => 0, 'actualizar' => 0, 'errores' => 0],
            ];
        }
        if (! array_intersect(['nombres', 'nombre_completo', 'apellidos'], array_keys($columns))) {
            return [
                'error' => 'No encontré la columna con el nombre. Debe llamarse «Nombres» (y «Apellidos»), o «Nombre completo».',
                'columns' => $columns, 'ignored' => $ignored, 'rows' => [], 'summary' => ['nuevos' => 0, 'actualizar' => 0, 'errores' => 0],
            ];
        }

        $rows = [];
        foreach (array_slice($sheet, $headerIndex + 1, null, true) as $index => $cells) {
            $values = [];
            foreach ($columns as $key => $position) {
                $values[$key] = $this->text($cells[$position] ?? null);
            }
            if (implode('', $values) === '') {
                continue;
            }
            $rows[] = $this->checkRow($index + 1, $values, $cells, $columns);
        }

        $this->checkAcrossRows($rows);

        $valid = collect($rows)->where('status', '!=', 'error');

        return [
            'error' => $rows === [] ? 'El archivo no tiene filas con datos bajo los títulos.' : null,
            'columns' => array_map(fn ($position) => (string) ($sheet[$headerIndex][$position] ?? ''), $columns),
            'ignored' => $ignored,
            'rows' => $rows,
            'summary' => [
                'nuevos' => $valid->where('status', 'nuevo')->unique('rut')->count(),
                'actualizar' => $valid->where('status', 'actualizar')->unique('rut')->count(),
                'errores' => collect($rows)->where('status', 'error')->count(),
            ],
        ];
    }

    /**
     * Importa las filas sin problemas de un análisis.
     *
     * @return array{created: int, updated: int, bank_accounts: int, balances: int}
     */
    public function import(array $analysis, User $user): array
    {
        $result = ['created' => 0, 'updated' => 0, 'bank_accounts' => 0, 'balances' => 0];
        $groups = collect($analysis['rows'])->where('status', '!=', 'error')->groupBy('rut');

        DB::transaction(function () use ($groups, $user, &$result) {
            foreach ($groups as $rut => $rows) {
                $first = $rows->first();
                $person = Person::where('rut', $rut)->first();

                $fields = array_filter([
                    'first_name' => $first['first_name'],
                    'last_name' => $first['last_name'],
                    'email' => $first['email'],
                    'phone' => $first['phone'],
                    'address' => $first['address'],
                ], fn ($v) => $v !== null && $v !== '');

                if ($person) {
                    $person->update($fields);
                    $result['updated']++;
                } else {
                    $person = Person::create($fields + ['rut' => $rut, 'role_type' => 'trabajador', 'is_enabled' => true]);
                    $result['created']++;
                }
                $account = app(PersonService::class)->openAccount($person);

                $hasDefault = PersonBankAccount::where('person_id', $person->id)->where('is_default', true)->exists();
                foreach ($rows->filter(fn ($r) => $r['bank_id'] && $r['account_number']) as $row) {
                    $bankAccount = PersonBankAccount::firstOrCreate(
                        ['person_id' => $person->id, 'bank_id' => $row['bank_id'], 'account_type_id' => $row['account_type_id'], 'account_number' => $row['account_number']],
                        ['is_default' => ! $hasDefault, 'is_active' => true],
                    );
                    if ($bankAccount->wasRecentlyCreated) {
                        $result['bank_accounts']++;
                        $hasDefault = true;
                    }
                }

                // La ficha muestra la primera cuenta del archivo, igual que las cuentas para transferencias
                $main = $rows->first(fn ($r) => $r['bank_id'] && $r['account_number']);
                if ($main) {
                    $person->update(['bank_id' => $main['bank_id'], 'account_type_id' => $main['account_type_id'], 'account_number' => $main['account_number']]);
                }

                if ($first['balance'] !== null && $first['balance'] != 0 && $this->canSetInitialBalance($account)) {
                    $account->update(['balance' => $first['balance']]);
                    activity('balance_adjustment')
                        ->performedOn($account)
                        ->causedBy($user)
                        ->withProperties(['old' => 0.0, 'new' => (float) $first['balance'], 'reason' => self::INITIAL_BALANCE_REASON])
                        ->log('Saldo inicial importado');
                    $result['balances']++;
                }
            }
        });

        return $result;
    }

    /** @return list<list<mixed>> */
    private function readSheet(string $path, string $extension): array
    {
        $extension = Str::lower($extension);
        $reader = match ($extension) {
            'csv', 'txt' => new CsvReader(new CsvOptions(
                FIELD_DELIMITER: $this->csvDelimiter($path),
                // Excel en Windows guarda los CSV en Windows-1252, no en UTF-8
                ENCODING: mb_check_encoding((string) file_get_contents($path), 'UTF-8') ? 'UTF-8' : 'Windows-1252',
            )),
            'ods' => new OdsReader(),
            default => new XlsxReader(),
        };

        $rows = [];
        $reader->open($path);
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
                if (count($rows) > 5000) {
                    break;
                }
            }
            break;
        }
        $reader->close();

        return $rows;
    }

    /** Excel en español guarda los CSV separados por punto y coma. */
    private function csvDelimiter(string $path): string
    {
        $line = (string) fgets(fopen($path, 'r'));

        return substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
    }

    /** @return array{0: ?int, 1: array<string, int>, 2: list<string>} */
    private function findHeader(array $sheet): array
    {
        $lookup = [];
        foreach (self::COLUMNS as $key => [$title, $names]) {
            foreach ([...$names, ...($title ? [$title] : [])] as $name) {
                $lookup[$this->normalize($name)] = $key;
            }
        }

        foreach (array_slice($sheet, 0, 10, true) as $index => $cells) {
            $columns = [];
            $ignored = [];
            foreach ($cells as $position => $cell) {
                $label = $this->text($cell);
                if ($label === '') {
                    continue;
                }
                $key = $lookup[$this->normalize($label)] ?? null;
                if ($key && ! isset($columns[$key])) {
                    $columns[$key] = $position;
                } else {
                    $ignored[] = $label;
                }
            }
            if (isset($columns['rut'])) {
                return [$index, $columns, $ignored];
            }
        }

        return [null, [], []];
    }

    /** @return array<string, mixed> */
    private function checkRow(int $line, array $v, array $cells, array $columns): array
    {
        $errors = [];
        $warnings = [];

        $rut = $this->rut($v['rut'] ?? '');
        if ($rut === null) {
            $errors[] = ($v['rut'] ?? '') === '' ? 'Falta el RUT.' : "El RUT «{$v['rut']}» no es válido.";
        }
        $person = $rut ? Person::where('rut', $rut)->first() : null;

        [$firstName, $lastName] = $this->names($v);
        if (! $person && ($firstName === '' || $lastName === '')) {
            $errors[] = $firstName === '' && $lastName === '' ? 'Falta el nombre.' : 'Falta el nombre o el apellido.';
        }

        $email = Str::lower($v['correo'] ?? '');
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $warnings[] = "El correo «{$email}» no es válido; se importa sin correo.";
            $email = '';
        } elseif ($email !== '' && Person::where('email', $email)->when($person, fn ($q) => $q->whereKeyNot($person->id))->exists()) {
            $warnings[] = "El correo «{$email}» ya lo tiene otra persona; se importa sin correo.";
            $email = '';
        }

        $phone = $this->number($cells[$columns['telefono'] ?? -1] ?? null) ?? ($v['telefono'] ?? '');
        if (mb_strlen($phone) > 20) {
            $warnings[] = 'El teléfono es demasiado largo; se importa sin teléfono.';
            $phone = '';
        }

        [$bankId, $bankName, $typeId, $typeName, $number, $bankWarnings] = $this->bankAccount($v, $cells, $columns, $rut);
        $warnings = [...$warnings, ...$bankWarnings];

        $balance = null;
        if (($v['saldo'] ?? '') !== '') {
            $balance = $this->money($cells[$columns['saldo']] ?? null);
            if ($balance === null) {
                $warnings[] = "El saldo «{$v['saldo']}» no se entiende como monto; se ignora.";
            } elseif ($balance != 0 && $person && ! $this->canSetInitialBalance(Account::where('type', 'person')->where('person_id', $person->id)->first())) {
                $warnings[] = 'Ya tiene movimientos en COTE; se ignora el saldo del Excel.';
                $balance = null;
            }
        }

        return [
            'line' => $line,
            'status' => $errors ? 'error' : ($person ? 'actualizar' : 'nuevo'),
            'errors' => $errors,
            'warnings' => $warnings,
            'rut' => $rut,
            'rut_formatted' => $rut ? RutHelper::format($rut) : ($v['rut'] ?? ''),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim("$firstName $lastName") ?: $person?->full_name,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'address' => ($v['direccion'] ?? '') ?: null,
            'bank_id' => $bankId,
            'bank_name' => $bankName,
            'account_type_id' => $typeId,
            'account_type_name' => $typeName,
            'account_number' => $number,
            'balance' => $balance,
        ];
    }

    /** RUT repetido = otra cuenta de la misma persona; correo repetido entre personas distintas no se usa. */
    private function checkAcrossRows(array &$rows): void
    {
        $seen = [];
        $emails = [];
        foreach ($rows as &$row) {
            if ($row['status'] === 'error') {
                continue;
            }
            if (isset($seen[$row['rut']])) {
                $row['extra'] = true;
                $row['warnings'][] = 'Mismo RUT que la fila ' . $seen[$row['rut']] . ': se agrega como otra cuenta bancaria.';
                if ($row['balance'] !== null) {
                    $row['warnings'][] = 'Se usa el saldo de la primera fila de esta persona.';
                }
                continue;
            }
            $seen[$row['rut']] = $row['line'];

            if ($row['email'] && isset($emails[$row['email']])) {
                $row['warnings'][] = "El correo «{$row['email']}» se repite en la fila {$emails[$row['email']]}; se importa sin correo.";
                $row['email'] = null;
            } elseif ($row['email']) {
                $emails[$row['email']] = $row['line'];
            }
        }
    }

    /** @return array{0: ?int, 1: ?string, 2: ?int, 3: ?string, 4: ?string, 5: list<string>} */
    private function bankAccount(array $v, array $cells, array $columns, ?string $rut): array
    {
        $warnings = [];
        $bankText = $v['banco'] ?? '';
        $typeText = $v['tipo_cuenta'] ?? '';
        $number = $this->number($cells[$columns['numero_cuenta'] ?? -1] ?? null)
            ?? preg_replace('/[^0-9Kk-]/', '', $v['numero_cuenta'] ?? '');

        $bankId = $bankText !== '' ? $this->matchBank($bankText) : null;
        $typeId = $typeText !== '' ? $this->matchAccountType($typeText) : null;

        // «Cuenta RUT» es de BancoEstado y su número es el RUT sin dígito verificador
        $isCuentaRut = $this->normalize($bankText) === 'cuenta rut' || ($typeId && $typeId === $this->matchAccountType('cuenta rut'));
        if ($isCuentaRut) {
            $bankId ??= $this->matchBank('banco estado');
            $typeId ??= $this->matchAccountType('cuenta rut');
            if ($number === '' && $rut) {
                $number = Str::before($rut, '-');
            }
        }

        if ($bankText !== '' && ! $bankId) {
            $warnings[] = "No reconozco el banco «{$bankText}»; se importa sin cuenta bancaria.";
        }
        if ($typeText !== '' && ! $typeId) {
            $warnings[] = "No reconozco el tipo de cuenta «{$typeText}»; la cuenta queda sin tipo.";
        }
        if ($bankId && $number === '') {
            $warnings[] = 'Falta el número de cuenta; se importa sin cuenta bancaria.';
        }
        if (! $bankId && $bankText === '' && $number !== '') {
            $warnings[] = 'Tiene número de cuenta pero no dice el banco; se importa sin cuenta bancaria.';
        }

        $valid = $bankId && $number !== '';

        return [
            $valid ? $bankId : null,
            $valid ? Bank::find($bankId)?->name : null,
            $valid ? $typeId : null,
            $valid && $typeId ? AccountType::find($typeId)?->name : null,
            $valid ? $number : null,
            $warnings,
        ];
    }

    private function matchBank(string $text): ?int
    {
        $this->banks ??= Bank::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [$this->normalize($name) => $id])->all();
        $needle = $this->normalize($text);

        if (isset($this->banks[$needle])) {
            return $this->banks[$needle];
        }
        foreach (self::BANK_ALIASES as $name => $aliases) {
            if (in_array($needle, $aliases, true)) {
                return $this->banks[$name] ?? null;
            }
        }

        // «Banco Falabella» = «falabella»: se compara sin las palabras comunes
        $core = fn (string $s) => trim(preg_replace('/\b(banco|de|del|chile|s a|sa)\b/', ' ', $s));
        $needleCore = $core($needle);
        if (mb_strlen($needleCore) < 4) {
            return null;
        }
        foreach ($this->banks as $name => $id) {
            if ($core($name) === $needleCore || str_contains($core($name), $needleCore)) {
                return $id;
            }
        }

        return null;
    }

    private function matchAccountType(string $text): ?int
    {
        $this->accountTypes ??= AccountType::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [$this->normalize($name) => $id])->all();
        $needle = $this->normalize($text);

        $name = match (true) {
            isset($this->accountTypes[$needle]) => $needle,
            str_contains($needle, 'corriente') || in_array($needle, ['cte', 'cta cte', 'cc'], true) => 'cuenta corriente',
            str_contains($needle, 'chequera') => 'chequera electronica',
            str_contains($needle, 'vista') => 'cuenta vista',
            str_contains($needle, 'rut') => 'cuenta rut',
            str_contains($needle, 'ahorro') => 'cuenta de ahorro',
            default => null,
        };

        return $name ? ($this->accountTypes[$name] ?? null) : null;
    }

    /** Solo una cuenta que todavía no se ha usado puede partir con saldo. */
    private function canSetInitialBalance(?Account $account): bool
    {
        if (! $account) {
            return true;
        }

        return (float) $account->balance == 0.0
            && ! Transaction::where(fn ($q) => $q->where('from_account_id', $account->id)->orWhere('to_account_id', $account->id))->exists()
            && ! Expense::where('account_id', $account->id)->where('status', 'approved')->exists();
    }

    /** @return array{0: string, 1: string} */
    private function names(array $v): array
    {
        $first = $v['nombres'] ?? '';
        $last = $v['apellidos'] ?? trim(($v['apellido_paterno'] ?? '') . ' ' . ($v['apellido_materno'] ?? ''));

        // Una columna «Nombre» sin «Apellidos» suele traer el nombre completo
        $full = $first === '' && $last === '' ? ($v['nombre_completo'] ?? '') : ($last === '' ? $first : '');
        if ($full !== '') {
            $first = $last = '';
            if (str_contains($full, ',')) {
                // «Pérez Soto, Juan Carlos»
                [$last, $first] = array_map('trim', explode(',', $full, 2));
            } else {
                // En Chile van dos apellidos al final: «Juan Pérez Soto», «Juan Carlos Pérez Soto»
                $words = preg_split('/\s+/', trim($full));
                $split = count($words) <= 3 ? 1 : count($words) - 2;
                $first = implode(' ', array_slice($words, 0, $split));
                $last = implode(' ', array_slice($words, $split));
            }
        }

        return [$this->properCase($first), $this->properCase($last)];
    }

    /** «JUAN PÉREZ» o «juan pérez» → «Juan Pérez»; lo escrito con mayúsculas y minúsculas se respeta. */
    private function properCase(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if ($text === mb_strtoupper($text) || $text === mb_strtolower($text)) {
            return mb_convert_case(mb_strtolower($text), MB_CASE_TITLE);
        }

        return $text;
    }

    private function rut(string $text): ?string
    {
        $clean = strtoupper(preg_replace('/[^0-9Kk]/', '', $text));
        if (strlen($clean) < 2) {
            return null;
        }
        $rut = substr($clean, 0, -1) . '-' . substr($clean, -1);

        return RutHelper::validate($rut) ? ltrim($rut, '0') : null;
    }

    /** Números que Excel guarda como cifra (teléfonos, cuentas) sin decimales ni notación científica. */
    private function number(mixed $cell): ?string
    {
        if (is_int($cell) || (is_float($cell) && floor($cell) === $cell)) {
            return sprintf('%.0f', $cell);
        }

        return null;
    }

    private function money(mixed $cell): ?float
    {
        if (is_int($cell) || is_float($cell)) {
            return round((float) $cell);
        }

        $text = trim((string) $cell);
        $negative = str_starts_with($text, '-') || (str_starts_with($text, '(') && str_ends_with($text, ')'));
        $digits = preg_replace('/[^0-9,]/', '', $text);
        $digits = Str::before($digits, ',');
        if ($digits === '') {
            return null;
        }

        return ($negative ? -1 : 1) * (float) $digits;
    }

    private function text(mixed $cell): string
    {
        return match (true) {
            $cell === null => '',
            $cell instanceof \DateTimeInterface => $cell->format('d-m-Y'),
            is_float($cell) && floor($cell) === $cell => sprintf('%.0f', $cell),
            is_bool($cell) => $cell ? 'Sí' : 'No',
            default => trim((string) $cell),
        };
    }

    private function normalize(string $text): string
    {
        $text = Str::lower(Str::ascii(str_replace(['°', 'º'], '', $text)));

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/', ' ', $text)));
    }
}
