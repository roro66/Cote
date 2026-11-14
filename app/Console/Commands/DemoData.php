<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\PeriodClosing;
use App\Models\User;
use App\Services\PeriodReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Historia de demostración simulada día a día: los saldos se calculan en orden cronológico,
 * así que la cartola, el libro de caja y los cierres cuadran como si se hubiera usado la app.
 * Lo creado queda anotado en un manifiesto para poder borrarlo sin tocar datos reales.
 */
class DemoData extends Command
{
    protected $signature = 'cote:datos-demo
        {--trabajadores=50 : Cantidad de trabajadores}
        {--desde=2021-01-01 : Primer día de la historia}
        {--sin-imagenes : No genera las fotos de las boletas}
        {--borrar : Elimina los datos de demostración creados antes}
        {--force : No pide confirmación en producción}';

    protected $description = 'Crea o borra datos de demostración: trabajadores, entregas, rendiciones con boletas, devoluciones y cierres de mes.';

    private const MANIFEST = 'datos-demo.json';

    private const EMAIL_DOMAIN = 'demo.cote.cl';

    private const HOLIDAYS = ['01-01', '05-01', '05-21', '09-18', '09-19', '12-25'];

    private const FONT = '/usr/share/fonts/truetype/dejavu/DejaVuSansMono.ttf';

    private const FONT_BOLD = '/usr/share/fonts/truetype/dejavu/DejaVuSansMono-Bold.ttf';

    private const MALE = ['Juan', 'Pedro', 'Luis', 'Carlos', 'José', 'Francisco', 'Diego', 'Felipe', 'Cristián', 'Rodrigo', 'Matías', 'Sebastián', 'Jorge', 'Manuel', 'Víctor', 'Héctor', 'Patricio', 'Claudio', 'Mauricio', 'Gonzalo', 'Nicolás', 'Andrés', 'Marcelo', 'Raúl', 'Sergio', 'Hernán', 'Ignacio', 'Tomás', 'Benjamín', 'Alejandro'];

    private const FEMALE = ['María', 'Carolina', 'Camila', 'Valentina', 'Francisca', 'Javiera', 'Daniela', 'Paula', 'Claudia', 'Patricia', 'Andrea', 'Fernanda', 'Constanza', 'Macarena', 'Lorena', 'Marcela', 'Alejandra', 'Catalina', 'Verónica', 'Isidora'];

    private const SURNAMES = ['González', 'Muñoz', 'Rojas', 'Díaz', 'Pérez', 'Soto', 'Contreras', 'Silva', 'Martínez', 'Sepúlveda', 'Morales', 'Rodríguez', 'López', 'Fuentes', 'Hernández', 'Torres', 'Araya', 'Flores', 'Espinoza', 'Valenzuela', 'Castillo', 'Tapia', 'Reyes', 'Gutiérrez', 'Castro', 'Pizarro', 'Álvarez', 'Vásquez', 'Sánchez', 'Fernández', 'Ramírez', 'Carrasco', 'Gómez', 'Cortés', 'Herrera', 'Núñez', 'Jara', 'Vergara', 'Rivera', 'Figueroa', 'Riquelme', 'García', 'Miranda', 'Bravo', 'Vera', 'Molina', 'Vega', 'Campos', 'Sandoval', 'Orellana'];

    private const STREETS = ['Av. Pajaritos', 'Los Aromos', 'Av. Vicuña Mackenna', 'Pasaje Las Lilas', 'Av. Independencia', 'Los Carrera', 'San Martín', 'Av. La Florida', 'Santa Rosa', 'Gran Avenida', 'Los Pinos', 'Av. Departamental', 'El Roble', 'Av. Recoleta', 'Las Acacias'];

    private const COMUNAS = ['Maipú', 'Puente Alto', 'La Florida', 'San Bernardo', 'Quilicura', 'Pudahuel', 'Peñalolén', 'La Pintana', 'El Bosque', 'Recoleta', 'Conchalí', 'Renca', 'Lo Prado', 'Cerro Navia', 'Buin'];

    private const PLACES = ['obra Maipú', 'faena Rancagua', 'mantención planta Quilicura', 'visita cliente Talca', 'obra Puente Alto', 'instalación San Bernardo', 'faena Los Andes', 'obra Melipilla', 'mantención bodega Pudahuel', 'instalación Colina', 'obra Buin', 'faena San Antonio'];

    /** Por categoría: peso, comercios, glosas, rango de monto (pesos de 2021) y documentos posibles. */
    private const SPENDING = [
        'Combustible' => [22, ['Copec', 'Shell', 'Aramco', 'Petrobras'], ['Bencina 93 camioneta', 'Petróleo diésel camioneta', 'Bencina 95 vehículo', 'Petróleo para generador'], 12000, 60000, ['boleta', 'boleta', 'factura']],
        'Peaje' => [10, ['Autopista Central', 'Ruta 68', 'Ruta del Maipo', 'Vespucio Norte', 'Costanera Norte', 'Autopista del Sol'], ['Peaje ida', 'Peaje ida y vuelta', 'Pase diario'], 1200, 7500, ['ticket', 'boleta']],
        'Alimentación' => [20, ['Restaurant El Rincón', 'Fuente de Soda La Esquina', 'Supermercado Líder', 'Unimarc', 'Santa Isabel', 'Panadería San Camilo', 'Picada Doña Rosa'], ['Almuerzo cuadrilla', 'Colación', 'Agua y bebidas para la cuadrilla', 'Almuerzo en terreno', 'Café y pan para la faena'], 3000, 38000, ['boleta']],
        'Insumos y Materiales' => [16, ['Sodimac', 'Easy', 'Construmart', 'Imperial', 'Ferretería El Clavo', 'Ferretería Don Lucho'], ['Tornillos y tarugos', 'Cable eléctrico 2,5 mm', 'Cinta aisladora y amarras', 'Pintura y rodillos', 'Silicona y sellante', 'Cañería PVC y fittings', 'Sacos de cemento'], 4000, 110000, ['boleta', 'factura', 'guia_despacho']],
        'Herramientas' => [6, ['Sodimac', 'Easy', 'Ferretería Industrial Santiago', 'Herramientas Chile'], ['Juego de brocas', 'Alicate universal', 'Huincha de medir', 'Disco de corte', 'Taladro percutor', 'Nivel de burbuja'], 6000, 140000, ['boleta', 'factura']],
        'Vulcanización' => [4, ['Vulcanización Los Andes', 'Vulcanización Don Pepe', 'Neumáticos Express'], ['Reparación de neumático', 'Cambio de válvula', 'Parche y balanceo'], 5000, 35000, ['boleta', 'vale']],
        'Hospedaje' => [3, ['Hostal Las Rosas', 'Hotel Diego de Almagro', 'Cabañas El Bosque', 'Residencial Doña Inés'], ['Alojamiento una noche', 'Alojamiento dos noches'], 25000, 90000, ['factura', 'boleta']],
        'Viáticos' => [8, ['Buses Pullman', 'Turbus', 'Uber', 'Radio Taxi Central', 'Metro (carga Bip!)'], ['Pasaje en bus', 'Traslado a faena', 'Carga tarjeta Bip!', 'Taxi a la obra'], 2000, 28000, ['boleta', 'ticket']],
        'Servicios' => [5, ['Estacionamientos Saba', 'Correos de Chile', 'Chilexpress', 'Copistería El Estudiante'], ['Estacionamiento', 'Envío de documentos', 'Fotocopias de planos', 'Encomienda de repuestos'], 1500, 25000, ['boleta', 'ticket']],
        'Mantenimiento' => [6, ['Lubricentro Rápido', 'Taller Mecánico Hermanos Soto', 'Mecánica Express', 'Frenos y Embragues Ltda.'], ['Cambio de aceite', 'Cambio de pastillas de freno', 'Revisión técnica', 'Cambio de batería'], 15000, 160000, ['boleta', 'factura']],
    ];

    private const NO_RECEIPT = [
        'Peaje' => 'Pórtico con TAG, sin comprobante',
        'Alimentación' => 'Colación en local sin boleta',
        'Viáticos' => 'Taxi sin boleta',
    ];

    private const REJECTIONS = ['Falta la boleta', 'La foto no se lee', 'El monto no coincide con la boleta', 'La fecha no corresponde', 'Falta explicar en qué se gastó'];

    private Carbon $now;

    private Carbon $start;

    /** Día que se está simulando. */
    private Carbon $clock;

    private int $treasuryId;

    private float $treasuryBalance;

    private float $treasuryInitial;

    private int $treasurerId;

    private int $bossId;

    private bool $images;

    /** @var array<string, int> */
    private array $categoryIds = [];

    /** @var list<array<string, mixed>> */
    private array $workers = [];

    /** @var list<array{0: int, 1: int, 2: float}> momento, trabajador y cambio de saldo pendientes (rendiciones por aprobar) */
    private array $effects = [];

    /** @var array<string, array<int, int>> */
    private array $sequences = [];

    /** @var array<string, true> */
    private array $docKeys = [];

    /** @var array<string, list<int>> */
    private array $created = ['people' => [], 'accounts' => [], 'transactions' => [], 'expenses' => [], 'closings' => []];

    public function handle(): int
    {
        if ($this->option('borrar')) {
            return $this->purge();
        }

        $disk = Storage::disk(Document::DISK);
        if ($disk->exists(self::MANIFEST)) {
            $this->error('Ya hay datos de demostración. Bórralos primero con --borrar.');

            return self::FAILURE;
        }

        if (! $this->prepare()) {
            return self::FAILURE;
        }

        if (app()->isProduction() && ! $this->option('force')
            && ! $this->confirm("Se crearán datos de demostración en PRODUCCIÓN desde {$this->start->format('d-m-Y')}. ¿Continuar?")) {
            return self::FAILURE;
        }

        mt_srand(20210101);
        $began = microtime(true);

        try {
            DB::transaction(function () {
                $this->createWorkers((int) $this->option('trabajadores'));
                $this->simulate();
                $this->saveBalances();
                $this->closeMonths();
            });
        } catch (\Throwable $e) {
            foreach ($this->created['expenses'] as $id) {
                $disk->deleteDirectory("expenses/{$id}");
            }
            throw $e;
        }

        $disk->put(self::MANIFEST, json_encode($this->created + [
            'treasury_id' => $this->treasuryId,
            'treasury_delta' => round($this->treasuryBalance - $this->treasuryInitial, 2),
            'created_at' => now()->toIso8601String(),
        ]));

        $this->table(['Qué', 'Cantidad'], [
            ['Trabajadores', count($this->created['people'])],
            ['Movimientos (ingresos, entregas, devoluciones)', count($this->created['transactions'])],
            ['Rendiciones', count($this->created['expenses'])],
            ['Ítems de gasto', DB::table('expense_items')->whereIn('expense_id', $this->created['expenses'])->count()],
            ['Meses cerrados', count($this->created['closings'])],
            ['Saldo final de la caja', '$' . number_format($this->treasuryBalance, 0, ',', '.')],
        ]);
        $this->info(sprintf('Listo en %.0f segundos.', microtime(true) - $began));

        return self::SUCCESS;
    }

    private function prepare(): bool
    {
        $this->now = now();
        $this->start = Carbon::parse($this->option('desde'))->startOfDay();
        $this->clock = $this->start->copy();
        $this->images = ! $this->option('sin-imagenes');

        $treasury = DB::table('accounts')->where('type', 'treasury')->first();
        $treasurer = User::whereHas('roles', fn ($q) => $q->where('name', 'treasurer'))->first();
        $boss = User::whereHas('roles', fn ($q) => $q->where('name', 'boss'))->first();
        if (! $treasury || ! ($treasurer || $boss)) {
            $this->error('Falta la cuenta Tesorería o un usuario Jefe/Tesorero.');

            return false;
        }

        $this->treasuryId = $treasury->id;
        $this->treasuryBalance = $this->treasuryInitial = (float) $treasury->balance;
        $this->treasurerId = ($treasurer ?? $boss)->id;
        $this->bossId = ($boss ?? $treasurer)->id;
        $this->categoryIds = DB::table('expense_categories')->pluck('id', 'name')->all();

        if ($this->images && ! (function_exists('imagettftext') && is_file(self::FONT))) {
            $this->warn('Sin GD con FreeType o sin la fuente DejaVu: las boletas se dibujan con la letra básica.');
        }

        return true;
    }

    private function createWorkers(int $count): void
    {
        $banks = DB::table('banks')->pluck('id')->all();
        $accountTypes = DB::table('account_types')->pluck('id')->all();
        $usedRuts = DB::table('people')->pluck('rut')->flip()->all();
        $usedNames = [];
        $lastHire = $this->now->copy()->subDays(45);
        $span = max(1, $this->start->diffInDays($lastHire));

        for ($i = 0; $i < $count; $i++) {
            do {
                $female = mt_rand(1, 100) <= 30;
                $first = $this->pick($female ? self::FEMALE : self::MALE);
                $last = $this->pick(self::SURNAMES) . ' ' . $this->pick(self::SURNAMES);
            } while (isset($usedNames["$first $last"]));
            $usedNames["$first $last"] = true;

            do {
                $number = mt_rand(8_000_000, 24_000_000);
                $rut = number_format($number, 0, '', '.') . '-' . $this->rutDv($number);
            } while (isset($usedRuts[$rut]));
            $usedRuts[$rut] = true;

            // La mitad parte con la caja; el resto entra de a poco
            $hire = $i < intdiv($count, 2)
                ? $this->start->copy()
                : $this->start->copy()->addDays(30 + mt_rand(0, max(0, (int) $span - 30)));
            $hire = $this->workday($hire);

            $leave = null;
            if ($i % 5 === 3 && $hire->diffInDays($this->now) > 400) {
                $leave = $this->workday($hire->copy()->addDays(mt_rand(200, (int) $hire->diffInDays($this->now) - 60)));
            }

            $at = $hire->copy()->setTime(9, mt_rand(0, 59));
            $personId = DB::table('people')->insertGetId([
                'first_name' => $first,
                'last_name' => $last,
                'rut' => $rut,
                'email' => Str::lower(Str::ascii(Str::before($first, ' ') . '.' . Str::before($last, ' '))) . ($i + 1) . '@' . self::EMAIL_DOMAIN,
                'phone' => '+56 9 5555 ' . str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT),
                'bank_id' => $banks ? $this->pick($banks) : null,
                'account_type_id' => $accountTypes ? $this->pick($accountTypes) : null,
                'account_number' => (string) mt_rand(10_000_000, 99_999_999),
                'address' => $this->pick(self::STREETS) . ' ' . mt_rand(100, 9999) . ', ' . $this->pick(self::COMUNAS),
                'role_type' => 'trabajador',
                'is_enabled' => true,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
            $accountId = DB::table('accounts')->insertGetId([
                'name' => "$first $last",
                'type' => 'person',
                'person_id' => $personId,
                'balance' => 0,
                'is_enabled' => true,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
            $this->created['people'][] = $personId;
            $this->created['accounts'][] = $accountId;

            $this->workers[] = [
                'person' => $personId,
                'account' => $accountId,
                'name' => "$first $last",
                'first' => $first,
                'hire' => $hire,
                'leave' => $leave,
                'gone' => false,
                'balance' => 0.0,
                'scale' => $this->pick([0.6, 0.8, 1.0, 1.0, 1.2, 1.5, 1.8]),
                'interval' => mt_rand(9, 18),
                'next_expense' => $hire->copy()->addDays(mt_rand(5, 12)),
                'last_delivery' => null,
                'busy_until' => 0,
            ];
        }
    }

    private function simulate(): void
    {
        $this->deposit($this->money(3_000_000), $this->workday($this->start->copy())->setTime(9, 5), 'Fondo inicial de caja chica');

        $year = null;
        for ($day = $this->start->copy(); $day->lte($this->now); $day->addDay()) {
            $this->clock = $day->copy();
            if ($year !== $day->year) {
                $year = $day->year;
                $this->line("Simulando {$year}…");
            }
            if ($day->isSunday() || in_array($day->format('m-d'), self::HOLIDAYS, true)) {
                continue;
            }
            if ($day->isSaturday() && mt_rand(1, 100) > 30) {
                continue;
            }

            // Cada trabajador actúa a una hora distinta; se procesan en orden para que los saldos sigan el reloj
            $turns = [];
            foreach ($this->workers as $w => $worker) {
                if ($worker['gone'] || $worker['hire']->gt($day) || ($day->isSaturday() && mt_rand(1, 100) > 30)) {
                    continue;
                }
                $at = $day->copy()->setTime(mt_rand(8, 17), mt_rand(0, 59), mt_rand(0, 59));
                if ($at->lte($this->now)) {
                    $turns[] = [$at, $w];
                }
            }
            usort($turns, fn ($a, $b) => $a[0] <=> $b[0]);

            foreach ($turns as [$at, $w]) {
                $this->applyEffects($at);
                $this->turn($w, $day, $at);
            }
        }
        $this->applyEffects($this->now);
    }

    private function turn(int $w, Carbon $day, Carbon $at): void
    {
        $worker = &$this->workers[$w];
        $pending = $this->pendingFor($w);

        if ($worker['leave'] && $day->gte($worker['leave'])) {
            if ($pending == 0.0 && $worker['busy_until'] <= $at->timestamp) {
                $this->settle($w, $at);
            }

            return;
        }

        if ($day->gte($worker['next_expense']) && $worker['busy_until'] <= $at->timestamp) {
            $this->submitExpense($w, $at);
            $worker['next_expense'] = $day->copy()->addDays($worker['interval'] + mt_rand(-3, 3));

            return;
        }

        $available = $worker['balance'] + $pending;
        $recent = $worker['last_delivery'] && $worker['last_delivery']->diffInDays($day) < 4;
        if ($available < $this->money(40_000 * $worker['scale']) && ! $recent && mt_rand(1, 100) <= ($worker['balance'] < 0 ? 15 : 45)) {
            $base = $this->round($this->money($this->pick([80_000, 100_000, 120_000, 150_000, 200_000, 250_000, 300_000]) * $worker['scale']), 10_000);
            if ($worker['balance'] < 0 && mt_rand(1, 100) <= 35) {
                $this->deliver($w, (int) -$worker['balance'], 'Pago de lo que se le debía por gastos rendidos', $at);
            } else {
                $owed = $worker['balance'] < 0 ? (int) -$worker['balance'] : 0;
                $this->deliver($w, $owed + $base, $owed ? 'Pago de lo adeudado y entrega para gastos' : $this->pick(['Entrega de dinero para gastos', 'Entrega para gastos de terreno', 'Fondo para ' . $this->pick(self::PLACES), 'Entrega para materiales y colaciones']), $at);
            }

            return;
        }

        if ($pending == 0.0 && $worker['balance'] > $this->money(150_000 * $worker['scale']) && mt_rand(1, 100) <= 3) {
            $amount = $this->round($worker['balance'] * mt_rand(30, 70) / 100, 5_000);
            if ($amount > 0) {
                $this->giveBack($w, $amount, 'Devolución de dinero sobrante', $at);
            }
        }
    }

    /** Antes de irse, el trabajador queda al día y su cuenta se desactiva. */
    private function settle(int $w, Carbon $at): void
    {
        $worker = &$this->workers[$w];
        if ($worker['balance'] > 0) {
            $this->giveBack($w, (int) $worker['balance'], 'Devolución por término de contrato', $at);
        } elseif ($worker['balance'] < 0) {
            $this->deliver($w, (int) -$worker['balance'], 'Pago final de lo adeudado por término de contrato', $at, false);
        }
        $worker['gone'] = true;
        $worker['left_at'] = $at->copy();
    }

    private function deliver(int $w, int $amount, string $description, Carbon $at, bool $mayAnnul = true): void
    {
        $worker = &$this->workers[$w];

        if ($mayAnnul && mt_rand(1, 100) === 1) {
            [$wrong, $reason] = $this->pick([
                [$amount + $this->pick([10_000, 50_000, 100_000]), 'Se digitó mal el monto; se registró de nuevo.'],
                [$amount, 'Quedó registrado dos veces.'],
            ]);
            $this->ensureCash($wrong, $at);
            $this->insertTransaction('transfer', $this->treasuryId, $worker['account'], $wrong, $description, $at, [
                'status' => 'annulled',
                'annulled_at' => $at->copy()->addMinutes(3),
                'annulled_by' => $this->treasurerId,
                'annul_reason' => $reason,
                'updated_at' => $at->copy()->addMinutes(3),
            ]);
            $at = $at->copy()->addMinutes(5);
        }

        $this->ensureCash($amount, $at);
        $this->insertTransaction('transfer', $this->treasuryId, $worker['account'], $amount, $description, $at);
        $this->treasuryBalance -= $amount;
        $worker['balance'] += $amount;
        $worker['last_delivery'] = $at->copy()->startOfDay();
    }

    private function giveBack(int $w, int $amount, string $description, Carbon $at): void
    {
        $worker = &$this->workers[$w];
        $this->insertTransaction('transfer', $worker['account'], $this->treasuryId, $amount, $description, $at);
        $this->treasuryBalance += $amount;
        $worker['balance'] -= $amount;
    }

    /** Si la caja no alcanza, entra dinero un rato antes. */
    private function ensureCash(int $needed, Carbon $at): void
    {
        if ($this->treasuryBalance >= $needed) {
            return;
        }

        $active = count(array_filter($this->workers, fn ($w) => ! $w['gone'] && $w['hire']->lte($at)));
        $amount = max(
            $this->round($needed - $this->treasuryBalance + 300_000, 100_000),
            $this->round($this->money(mt_rand(15, 40) * 100_000) * max(1, $active / 25), 100_000),
        );
        $this->deposit((int) $amount, $at->copy()->subMinutes(mt_rand(10, 50)));
    }

    private function deposit(int $amount, Carbon $at, ?string $description = null): void
    {
        $description ??= $this->pick([
            'Cheque N° ' . mt_rand(4_000_000, 4_999_999) . ' de la empresa',
            'Transferencia desde la cuenta corriente de la empresa',
            'Reposición de caja chica ' . $at->locale('es')->translatedFormat('F Y'),
            'Transferencia de gerencia para caja chica',
        ]);
        $this->insertTransaction('deposit', null, $this->treasuryId, $amount, $description, $at);
        $this->treasuryBalance += $amount;
    }

    private function insertTransaction(string $type, ?int $from, int $to, int $amount, string $description, Carbon $at, array $extra = []): void
    {
        $user = mt_rand(1, 100) <= 80 ? $this->treasurerId : $this->bossId;
        $this->created['transactions'][] = DB::table('transactions')->insertGetId(array_merge([
            'transaction_number' => $this->nextNumber('transactions', 'transaction_number', 'TXN', $at->year),
            'type' => $type,
            'from_account_id' => $from,
            'to_account_id' => $to,
            'amount' => $amount,
            'description' => $description,
            'created_by' => $user,
            'approved_by' => $user,
            'status' => 'approved',
            'approved_at' => $at,
            'is_enabled' => true,
            'created_at' => $at,
            'updated_at' => $at,
        ], $extra));
    }

    private function submitExpense(int $w, Carbon $at): void
    {
        $worker = &$this->workers[$w];
        $inflation = $this->inflation($at);
        $target = $worker['balance'] > 30_000
            ? $worker['balance'] * mt_rand(60, 135) / 100
            : mt_rand(15_000, 90_000) * $worker['scale'] * $inflation;

        $from = $at->copy()->subDays(12)->max($worker['hire'])->startOfDay();
        $items = [];
        $total = 0;
        do {
            $items[] = $item = $this->makeItem($from, $at, $inflation);
            $total += $item['amount'];
        } while ($total < $target && count($items) < 6);

        $reviewAt = $this->reviewTime($at);
        $status = $reviewAt->gt($this->now) ? 'submitted' : (mt_rand(1, 100) <= 4 ? 'rejected' : 'approved');
        $reviewer = mt_rand(1, 100) <= 85 ? $this->treasurerId : $this->bossId;
        $lastDate = max(array_column($items, 'expense_date'));
        $firstDate = min(array_column($items, 'expense_date'));

        $expenseId = DB::table('expenses')->insertGetId([
            'expense_number' => $this->nextNumber('expenses', 'expense_number', 'RND', $at->year),
            'account_id' => $worker['account'],
            'submitted_by' => $worker['person'],
            'total_amount' => $total,
            'description' => $this->expenseDescription(Carbon::parse($firstDate), Carbon::parse($lastDate)),
            'expense_date' => $lastDate,
            'status' => $status,
            'reviewed_by' => $status === 'submitted' ? null : $reviewer,
            'submitted_at' => $at,
            'reviewed_at' => $status === 'submitted' ? null : $reviewAt,
            'rejection_reason' => $status === 'rejected' ? $this->pick(self::REJECTIONS) : null,
            'is_enabled' => true,
            'created_at' => $at,
            'updated_at' => $status === 'submitted' ? $at : $reviewAt,
        ]);
        $this->created['expenses'][] = $expenseId;

        foreach ($items as $item) {
            $itemId = DB::table('expense_items')->insertGetId([
                'expense_id' => $expenseId,
                'document_type' => $item['document_type'],
                'document_number' => $item['document_number'],
                'without_receipt' => $item['without_receipt'],
                'without_receipt_reason' => $item['without_receipt_reason'],
                'vendor_name' => $item['vendor_name'],
                'description' => $item['description'],
                'amount' => $item['amount'],
                'expense_date' => $item['expense_date'],
                'expense_category_id' => $item['category_id'],
                'is_enabled' => true,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
            if (! $item['without_receipt'] && $this->images) {
                $this->storeReceipt($expenseId, $itemId, $item, $at);
            }
        }

        if ($status === 'approved') {
            $this->effects[] = [$reviewAt->timestamp, $w, -(float) $total];
        }
        $worker['busy_until'] = $status === 'submitted' ? PHP_INT_MAX : $reviewAt->timestamp;
    }

    /** @return array<string, mixed> */
    private function makeItem(Carbon $from, Carbon $to, float $inflation): array
    {
        $category = $this->weighted(array_map(fn ($c) => $c[0], self::SPENDING));
        [, $vendors, $descriptions, $min, $max, $documents] = self::SPENDING[$category];

        $amount = mt_rand($min, $max) * $inflation;
        $amount = mt_rand(1, 100) <= 60 ? max(990, floor($amount / 1000) * 1000 + 990) : max(500, round($amount, -1));

        $type = $this->pick($documents);
        $vendor = $this->pick($vendors);
        $withoutReceipt = mt_rand(1, 100) <= ($category === 'Peaje' ? 25 : 3);
        do {
            $number = (string) mt_rand(100_000, 9_999_999);
        } while (isset($this->docKeys["$type|$vendor|$number"]));
        $this->docKeys["$type|$vendor|$number"] = true;

        $date = Carbon::createFromTimestamp(mt_rand($from->timestamp, $to->copy()->startOfDay()->timestamp), $to->getTimezone());

        return [
            'category_id' => $this->categoryIds[$category] ?? null,
            'vendor_name' => $vendor,
            'description' => $this->pick($descriptions),
            'amount' => (int) $amount,
            'document_type' => $type,
            'document_number' => $withoutReceipt ? null : $number,
            'without_receipt' => $withoutReceipt,
            'without_receipt_reason' => $withoutReceipt ? (self::NO_RECEIPT[$category] ?? 'El comercio no entregó boleta') : null,
            'expense_date' => $date->toDateString(),
            'time' => sprintf('%02d:%02d', mt_rand(8, 20), mt_rand(0, 59)),
        ];
    }

    private function storeReceipt(int $expenseId, int $itemId, array $item, Carbon $at): void
    {
        $jpeg = $this->receiptImage($item);
        $name = "{$item['document_type']}_{$item['document_number']}.jpg";
        $path = "expenses/{$expenseId}/items/{$itemId}/{$name}";
        Storage::disk(Document::DISK)->put($path, $jpeg);

        DB::table('documents')->insert([
            'name' => $name,
            'file_path' => $path,
            'mime_type' => 'image/jpeg',
            'file_size' => strlen($jpeg),
            'document_type' => $item['document_type'],
            'expense_item_id' => $itemId,
            'uploaded_by' => $this->treasurerId,
            'is_enabled' => true,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    private function receiptImage(array $item): string
    {
        $title = [
            'boleta' => 'BOLETA ELECTRÓNICA',
            'factura' => 'FACTURA ELECTRÓNICA',
            'ticket' => 'TICKET',
            'vale' => 'VALE',
            'guia_despacho' => 'GUÍA DE DESPACHO',
        ][$item['document_type']];
        $rutNumber = mt_rand(76_000_000, 77_999_999);
        $total = '$ ' . number_format($item['amount'], 0, ',', '.');

        $lines = [
            ['b', Str::upper($item['vendor_name'])],
            ['c', 'R.U.T.: ' . number_format($rutNumber, 0, '', '.') . '-' . $this->rutDv($rutNumber)],
            ['c', ''],
            ['b', $title],
            ['c', 'N° ' . $item['document_number']],
            ['c', ''],
            ['l', 'Fecha: ' . Carbon::parse($item['expense_date'])->format('d-m-Y') . '   ' . $item['time']],
            ['l', str_repeat('-', 30)],
            ...array_map(fn ($l) => ['l', $l], explode("\n", wordwrap($item['description'], 30, "\n", true))),
            ['l', '1 x ' . $total],
            ['l', str_repeat('-', 30)],
            ['b', 'TOTAL ' . $total],
            ['c', ''],
            ['c', 'Gracias por su preferencia'],
        ];

        $width = 360;
        $height = 40 + count($lines) * 24;
        $im = imagecreatetruecolor($width, $height);
        imagefill($im, 0, 0, imagecolorallocate($im, 252, 251, 245));
        $ink = imagecolorallocate($im, 35, 35, 40);
        $ttf = function_exists('imagettftext') && is_file(self::FONT);

        $y = 34;
        foreach ($lines as [$style, $text]) {
            if ($ttf) {
                $font = $style === 'b' && is_file(self::FONT_BOLD) ? self::FONT_BOLD : self::FONT;
                $box = imagettfbbox(12, 0, $font, $text);
                $x = $style === 'l' ? 22 : (int) (($width - ($box[2] - $box[0])) / 2);
                imagettftext($im, 12, 0, $x, $y, $ink, $font, $text);
            } else {
                imagestring($im, 4, 22, $y - 14, Str::ascii($text), $ink);
            }
            $y += 24;
        }

        ob_start();
        imagejpeg($im, null, 70);
        imagedestroy($im);

        return (string) ob_get_clean();
    }

    private function saveBalances(): void
    {
        foreach ($this->workers as $worker) {
            $at = $worker['left_at'] ?? $this->now;
            DB::table('accounts')->where('id', $worker['account'])->update([
                'balance' => round($worker['balance'], 2),
                'is_enabled' => ! $worker['gone'],
                'updated_at' => $at,
            ]);
            if ($worker['gone']) {
                DB::table('people')->where('id', $worker['person'])->update(['is_enabled' => false, 'updated_at' => $at]);
            }
        }
        DB::table('accounts')->where('id', $this->treasuryId)->update(['balance' => round($this->treasuryBalance, 2), 'updated_at' => $this->now]);
    }

    /** Cierra todos los meses menos los dos últimos, contando billetes como lo haría el tesorero. */
    private function closeMonths(): void
    {
        $reports = app(PeriodReportService::class);
        $last = $this->now->copy()->startOfMonth()->subMonths(2);
        $this->line('Cerrando meses…');

        for ($month = $this->start->copy()->startOfMonth(); $month->lte($last); $month->addMonth()) {
            $report = $reports->build($month);
            $system = (int) round($report['totals']['treasury_closing']);
            $diff = mt_rand(1, 100) <= 85 ? 0 : $this->pick([-10_000, -5_000, -2_000, -1_000, -500, 500, 1_000, 2_000]);
            $counted = max(0, $system + $diff);
            $cash = min($counted, $this->round(mt_rand(80_000, 450_000), 1_000));
            $closedAt = $this->workday($month->copy()->addMonth()->addDays(mt_rand(0, 5)))->setTime(mt_rand(17, 19), mt_rand(0, 59));
            if ($closedAt->gt($this->now)) {
                $closedAt = $this->now->copy();
            }

            $notes = match (true) {
                $diff < 0 => $this->pick(['Faltaron $' . number_format(-$diff, 0, ',', '.') . '; se revisará con los vales del mes.', 'Diferencia menor, probablemente un vuelto mal entregado.']),
                $diff > 0 => 'Sobraron $' . number_format($diff, 0, ',', '.') . '; posible vuelto no registrado.',
                mt_rand(1, 100) <= 20 => 'Sin novedades.',
                default => null,
            };

            $this->created['closings'][] = DB::table('period_closings')->insertGetId([
                'period' => $month->toDateString(),
                'closed_by' => $this->treasurerId,
                'closed_at' => $closedAt,
                'treasury_counted' => $counted,
                'count_detail' => json_encode(['billetes_y_monedas' => $this->bills($cash), 'banco' => $counted - $cash]),
                'notes' => $notes,
                'snapshot' => json_encode(['rows' => $report['rows'], 'totals' => $report['totals']]),
                'created_at' => $closedAt,
                'updated_at' => $closedAt,
            ]);
        }
    }

    /** @return array<int, int> */
    private function bills(int $cash): array
    {
        $bills = [];
        foreach (PeriodClosing::DENOMINATIONS as $value) {
            $qty = intdiv($cash, $value);
            if ($value === 20_000) {
                $qty = min($qty, mt_rand(2, 12));
            }
            if ($qty > 0) {
                $bills[$value] = $qty;
                $cash -= $qty * $value;
            }
        }

        return $bills;
    }

    private function purge(): int
    {
        $disk = Storage::disk(Document::DISK);
        if (! $disk->exists(self::MANIFEST)) {
            $this->error('No hay datos de demostración que borrar.');

            return self::FAILURE;
        }
        $m = json_decode($disk->get(self::MANIFEST), true);

        $demoTransactions = array_flip($m['transactions']);
        $demoExpenses = array_flip($m['expenses']);
        $foreign = DB::table('transactions')
            ->where(fn ($q) => $q->whereIn('from_account_id', $m['accounts'])->orWhereIn('to_account_id', $m['accounts']))
            ->pluck('id')->reject(fn ($id) => isset($demoTransactions[$id]))->count()
            + DB::table('expenses')->whereIn('account_id', $m['accounts'])
                ->pluck('id')->reject(fn ($id) => isset($demoExpenses[$id]))->count();
        if ($foreign > 0) {
            $this->error("Hay {$foreign} movimientos o rendiciones nuevos con trabajadores de demostración. Anúlalos o elimínalos antes de borrar.");

            return self::FAILURE;
        }

        if (app()->isProduction() && ! $this->option('force')
            && ! $this->confirm('Se borrarán ' . count($m['people']) . ' trabajadores de demostración con toda su historia. ¿Continuar?')) {
            return self::FAILURE;
        }

        DB::transaction(function () use ($m) {
            foreach (array_chunk($m['expenses'], 1000) as $ids) {
                $items = DB::table('expense_items')->whereIn('expense_id', $ids)->pluck('id')->all();
                foreach (array_chunk($items, 1000) as $itemIds) {
                    DB::table('documents')->whereIn('expense_item_id', $itemIds)->delete();
                    DB::table('expense_items')->whereIn('id', $itemIds)->delete();
                }
                DB::table('expenses')->whereIn('id', $ids)->delete();
            }
            foreach (array_chunk($m['transactions'], 1000) as $ids) {
                DB::table('transactions')->whereIn('id', $ids)->delete();
            }
            DB::table('period_closings')->whereIn('id', $m['closings'])->delete();
            DB::table('users')->whereIn('person_id', $m['people'])->update(['person_id' => null]);
            DB::table('person_bank_accounts')->whereIn('person_id', $m['people'])->delete();
            DB::table('accounts')->whereIn('id', $m['accounts'])->delete();
            DB::table('people')->whereIn('id', $m['people'])->delete();
            DB::table('accounts')->where('id', $m['treasury_id'])->decrement('balance', $m['treasury_delta']);
        });

        foreach ($m['expenses'] as $id) {
            $disk->deleteDirectory("expenses/{$id}");
        }
        $disk->delete(self::MANIFEST);

        $this->info('Datos de demostración borrados: ' . count($m['people']) . ' trabajadores, ' . count($m['transactions']) . ' movimientos, ' . count($m['expenses']) . ' rendiciones y ' . count($m['closings']) . ' cierres.');

        return self::SUCCESS;
    }

    private function applyEffects(Carbon $until): void
    {
        foreach ($this->effects as $k => [$at, $w, $delta]) {
            if ($at <= $until->timestamp) {
                $this->workers[$w]['balance'] += $delta;
                unset($this->effects[$k]);
            }
        }
    }

    private function pendingFor(int $w): float
    {
        $sum = 0.0;
        foreach ($this->effects as [, $worker, $delta]) {
            if ($worker === $w) {
                $sum += $delta;
            }
        }

        return $sum;
    }

    private function nextNumber(string $table, string $column, string $prefix, int $year): string
    {
        $this->sequences[$prefix][$year] ??= (int) DB::table($table)->where($column, 'like', "{$prefix}-{$year}-%")
            ->pluck($column)->map(fn ($n) => (int) Str::afterLast($n, '-'))->max();

        return sprintf('%s-%d-%06d', $prefix, $year, ++$this->sequences[$prefix][$year]);
    }

    private function reviewTime(Carbon $submitted): Carbon
    {
        return $this->workday($submitted->copy()->addDays(mt_rand(1, 7)))->setTime(mt_rand(9, 18), mt_rand(0, 59), mt_rand(0, 59));
    }

    private function expenseDescription(Carbon $first, Carbon $last): string
    {
        $month = $last->locale('es')->translatedFormat('F');

        return match (mt_rand(1, 3)) {
            1 => $first->isSameMonth($last)
                ? "Gastos del {$first->day} al {$last->day} de {$month}"
                : "Gastos del {$first->day} de {$first->locale('es')->translatedFormat('F')} al {$last->day} de {$month}",
            2 => 'Gastos ' . $this->pick(self::PLACES),
            default => 'Rendición ' . ($last->day <= 15 ? 'primera' : 'segunda') . " quincena de {$month}",
        };
    }

    private function workday(Carbon $day): Carbon
    {
        while ($day->isWeekend() || in_array($day->format('m-d'), self::HOLIDAYS, true)) {
            $day->addDay();
        }

        return $day;
    }

    /** Montos de 2021 llevados a la fecha con ~5,5% de alza anual. */
    private function inflation(Carbon $at): float
    {
        return 1 + 0.055 * max(0, $this->start->diffInDays($at) / 365);
    }

    private function money(float $amount): float
    {
        return $amount * $this->inflation($this->clock);
    }

    private function round(float $amount, int $to): int
    {
        return (int) (round($amount / $to) * $to);
    }

    private function rutDv(int $number): string
    {
        $sum = 0;
        $factor = 2;
        while ($number > 0) {
            $sum += ($number % 10) * $factor;
            $number = intdiv($number, 10);
            $factor = $factor === 7 ? 2 : $factor + 1;
        }
        $dv = 11 - ($sum % 11);

        return match ($dv) {
            11 => '0',
            10 => 'K',
            default => (string) $dv,
        };
    }

    /** @param  array<string, int>  $weights */
    private function weighted(array $weights): string
    {
        $roll = mt_rand(1, array_sum($weights));
        foreach ($weights as $key => $weight) {
            if (($roll -= $weight) <= 0) {
                return $key;
            }
        }

        return array_key_first($weights);
    }

    private function pick(array $options): mixed
    {
        return $options[array_rand($options)];
    }
}
