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

    private const FONT = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';

    private const FONT_BOLD = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

    /** Letra que imita lo escrito a mano en la planilla. */
    private const FONT_HAND = '/usr/share/fonts/truetype/dejavu/DejaVuSansMono-Oblique.ttf';

    private const MALE = ['Juan', 'Pedro', 'Luis', 'Carlos', 'José', 'Francisco', 'Diego', 'Felipe', 'Cristián', 'Rodrigo', 'Matías', 'Sebastián', 'Jorge', 'Manuel', 'Víctor', 'Héctor', 'Patricio', 'Claudio', 'Mauricio', 'Gonzalo', 'Nicolás', 'Andrés', 'Marcelo', 'Raúl', 'Sergio', 'Hernán', 'Ignacio', 'Tomás', 'Benjamín', 'Alejandro'];

    private const FEMALE = ['María', 'Carolina', 'Camila', 'Valentina', 'Francisca', 'Javiera', 'Daniela', 'Paula', 'Claudia', 'Patricia', 'Andrea', 'Fernanda', 'Constanza', 'Macarena', 'Lorena', 'Marcela', 'Alejandra', 'Catalina', 'Verónica', 'Isidora'];

    private const SURNAMES = ['González', 'Muñoz', 'Rojas', 'Díaz', 'Pérez', 'Soto', 'Contreras', 'Silva', 'Martínez', 'Sepúlveda', 'Morales', 'Rodríguez', 'López', 'Fuentes', 'Hernández', 'Torres', 'Araya', 'Flores', 'Espinoza', 'Valenzuela', 'Castillo', 'Tapia', 'Reyes', 'Gutiérrez', 'Castro', 'Pizarro', 'Álvarez', 'Vásquez', 'Sánchez', 'Fernández', 'Ramírez', 'Carrasco', 'Gómez', 'Cortés', 'Herrera', 'Núñez', 'Jara', 'Vergara', 'Rivera', 'Figueroa', 'Riquelme', 'García', 'Miranda', 'Bravo', 'Vera', 'Molina', 'Vega', 'Campos', 'Sandoval', 'Orellana'];

    private const STREETS = ['Av. Presidente Ibáñez', 'Los Aromos', 'Av. Angelmó', 'Pasaje Las Lilas', 'Av. Salvador Allende', 'Los Carrera', 'San Martín', 'Av. Diego Portales', 'Urmeneta', 'Av. Austral', 'Los Pinos', 'Av. Capitán Ávalos', 'El Roble', 'Av. Vicente Pérez Rosales', 'Las Acacias'];

    private const COMUNAS = ['Puerto Montt', 'Puerto Varas', 'Llanquihue', 'Frutillar', 'Calbuco', 'Alerce', 'Osorno', 'Maullín', 'Los Muermos'];

    /** Proyectos u obras que se anotan en la columna «Proyecto» de la planilla. */
    private const PROJECTS = ['Lago Chapo', 'Transición', 'Cuentas varias', 'Alerce Norte', 'Chamiza', 'Mantención Calbuco', 'Planta Llanquihue', 'Obra Frutillar', 'Costanera Puerto Varas', 'Correntoso'];

    private const CITIES = ['Puerto Montt', 'Puerto Varas', 'Llanquihue', 'Frutillar', 'Calbuco', 'Alerce', 'Osorno', 'Maullín'];

    /** Por categoría: peso, comercios, glosas, rango de monto (pesos de 2021) y documentos posibles. */
    private const SPENDING = [
        'Combustible' => [22, ['Copec', 'Shell', 'Aramco', 'Petrobras'], ['Bencina 93 camioneta', 'Petróleo diésel camioneta', 'Bencina 95 vehículo', 'Petróleo para generador'], 12000, 60000, ['boleta', 'boleta', 'factura']],
        'Peaje' => [10, ['Ruta de los Lagos', 'Ruta 5 Sur', 'Autopista Los Lagos', 'Ruta 215 Osorno'], ['Peaje ida', 'Peaje ida y vuelta', 'Pase diario'], 1200, 7500, ['ticket', 'boleta']],
        'Alimentación' => [20, ['Restaurant El Rincón', 'Fuente de Soda La Esquina', 'Supermercado Líder', 'Unimarc', 'Santa Isabel', 'Panadería San Camilo', 'Picada Doña Rosa'], ['Almuerzo cuadrilla', 'Colación', 'Agua y bebidas para la cuadrilla', 'Almuerzo en terreno', 'Café y pan para la faena'], 3000, 38000, ['boleta']],
        'Insumos y Materiales' => [16, ['Sodimac', 'Easy', 'Construmart', 'Imperial', 'Ferretería El Clavo', 'Ferretería Don Lucho'], ['Tornillos y tarugos', 'Cable eléctrico 2,5 mm', 'Cinta aisladora y amarras', 'Pintura y rodillos', 'Silicona y sellante', 'Cañería PVC y fittings', 'Sacos de cemento'], 4000, 110000, ['boleta', 'factura', 'guia_despacho']],
        'Herramientas' => [6, ['Sodimac', 'Easy', 'Ferretería Industrial Santiago', 'Herramientas Chile'], ['Juego de brocas', 'Alicate universal', 'Huincha de medir', 'Disco de corte', 'Taladro percutor', 'Nivel de burbuja'], 6000, 140000, ['boleta', 'factura']],
        'Vulcanización' => [4, ['Vulcanización Los Andes', 'Vulcanización Don Pepe', 'Neumáticos Express'], ['Reparación de neumático', 'Cambio de válvula', 'Parche y balanceo'], 5000, 35000, ['boleta', 'vale']],
        'Hospedaje' => [3, ['Hostal Las Rosas', 'Hotel Diego de Almagro', 'Cabañas El Bosque', 'Residencial Doña Inés'], ['Alojamiento una noche', 'Alojamiento dos noches'], 25000, 90000, ['factura', 'boleta']],
        'Viáticos' => [8, ['Buses Pullman', 'Turbus', 'Uber', 'Radio Taxi Central', 'Metro (carga Bip!)'], ['Pasaje en bus', 'Traslado a faena', 'Carga tarjeta Bip!', 'Taxi a la obra'], 2000, 28000, ['boleta', 'ticket']],
        'Servicios' => [5, ['Estacionamientos Saba', 'Correos de Chile', 'Chilexpress', 'Copistería El Estudiante'], ['Estacionamiento', 'Envío de documentos', 'Fotocopias de planos', 'Encomienda de repuestos'], 1500, 25000, ['boleta', 'ticket']],
        'Mantenimiento' => [6, ['Lubricentro Rápido', 'Taller Mecánico Hermanos Soto', 'Mecánica Express', 'Frenos y Embragues Ltda.'], ['Cambio de aceite', 'Cambio de pastillas de freno', 'Revisión técnica', 'Cambio de batería'], 15000, 160000, ['boleta', 'factura']],
    ];

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
                $rut = $number . '-' . $this->rutDv($number);
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
                'rut' => $rut,
                'hire' => $hire,
                'leave' => $leave,
                'gone' => false,
                'balance' => 0.0,
                'scale' => $this->pick([0.6, 0.8, 1.0, 1.0, 1.2, 1.5, 1.8]),
                'interval' => mt_rand(9, 18),
                'next_expense' => $hire->copy()->addDays(mt_rand(5, 12)),
                'last_delivery' => null,
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
                $this->turn($w, $day, $at);
            }
        }
    }

    private function turn(int $w, Carbon $day, Carbon $at): void
    {
        $worker = &$this->workers[$w];

        if ($worker['leave'] && $day->gte($worker['leave'])) {
            $this->settle($w, $at);

            return;
        }

        if ($day->gte($worker['next_expense'])) {
            $this->submitExpense($w, $at);
            $worker['next_expense'] = $day->copy()->addDays($worker['interval'] + mt_rand(-3, 3));

            return;
        }

        $available = $worker['balance'];
        $recent = $worker['last_delivery'] && $worker['last_delivery']->diffInDays($day) < 4;
        if ($available < $this->money(40_000 * $worker['scale']) && ! $recent && mt_rand(1, 100) <= ($worker['balance'] < 0 ? 15 : 45)) {
            $base = $this->round($this->money($this->pick([80_000, 100_000, 120_000, 150_000, 200_000, 250_000, 300_000]) * $worker['scale']), 10_000);
            if ($worker['balance'] < 0 && mt_rand(1, 100) <= 35) {
                $this->deliver($w, (int) -$worker['balance'], 'Pago de lo que se le debía por gastos rendidos', $at);
            } else {
                $owed = $worker['balance'] < 0 ? (int) -$worker['balance'] : 0;
                $this->deliver($w, $owed + $base, $owed ? 'Pago de lo adeudado y entrega para gastos' : $this->pick(['Entrega de dinero para gastos', 'Entrega para gastos de terreno', 'Fondo para ' . $this->pick(self::PROJECTS), 'Entrega para materiales y colaciones']), $at);
            }

            return;
        }

        if ($worker['balance'] > $this->money(150_000 * $worker['scale']) && mt_rand(1, 100) <= 3) {
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

    /**
     * El trabajador manda la planilla en papel y el tesorero la registra: queda aprobada en ese momento.
     * Unas pocas tienen la aprobación anulada y esperan corrección.
     */
    private function submitExpense(int $w, Carbon $at): void
    {
        $worker = &$this->workers[$w];
        $inflation = $this->inflation($at);
        $target = $worker['balance'] > 30_000
            ? $worker['balance'] * mt_rand(60, 135) / 100
            : mt_rand(15_000, 90_000) * $worker['scale'] * $inflation;

        $project = $this->pick(self::PROJECTS);
        $city = $this->pick(self::CITIES);
        $from = $at->copy()->subDays(12)->max($worker['hire'])->startOfDay();
        $items = [];
        $total = 0;
        do {
            $items[] = $item = $this->makeItem($from, $at, $inflation, $project, $city);
            $total += $item['amount'];
        } while ($total < $target && count($items) < 7);
        usort($items, fn ($a, $b) => $a['expense_date'] <=> $b['expense_date']);

        $annulled = mt_rand(1, 100) <= 2;
        $reviewer = mt_rand(1, 100) <= 85 ? $this->treasurerId : $this->bossId;
        $lastDate = max(array_column($items, 'expense_date'));

        $expenseId = DB::table('expenses')->insertGetId([
            'expense_number' => $this->nextNumber('expenses', 'expense_number', 'RND', $at->year),
            'account_id' => $worker['account'],
            'submitted_by' => $worker['person'],
            'total_amount' => $total,
            'description' => mt_rand(1, 100) <= 50 ? 'Rendición del ' . $at->format('d/m/Y') : "Gastos {$project}",
            'expense_date' => $lastDate,
            'status' => $annulled ? 'rejected' : 'approved',
            'reviewed_by' => $reviewer,
            'submitted_at' => $at,
            'reviewed_at' => $at,
            'rejection_reason' => $annulled ? 'Aprobación anulada: ' . $this->pick(['Monto mal copiado de la planilla', 'Se registró a nombre de otro trabajador', 'Faltó una boleta en la foto']) : null,
            'is_enabled' => true,
            'source' => 'received',
            'declared_total' => $total,
            'registered_by' => $reviewer,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
        $this->created['expenses'][] = $expenseId;

        foreach ($items as $item) {
            DB::table('expense_items')->insert([
                'expense_id' => $expenseId,
                'document_type' => $item['document_type'],
                'document_number' => $item['document_number'],
                'without_receipt' => false,
                'vendor_name' => $item['vendor_name'],
                'description' => $item['category'],
                'amount' => $item['amount'],
                'expense_date' => $item['expense_date'],
                'expense_category_id' => $item['category_id'],
                'project' => $item['project'],
                'city' => $item['city'],
                'is_enabled' => true,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        }
        if ($this->images) {
            $this->storePlanilla($expenseId, $worker, $items, $total, $at, $reviewer);
        }

        if (! $annulled) {
            $worker['balance'] -= $total;
        }
    }

    /** @return array<string, mixed> */
    private function makeItem(Carbon $from, Carbon $to, float $inflation, string $project, string $city): array
    {
        $category = $this->weighted(array_map(fn ($c) => $c[0], self::SPENDING));
        [, $vendors, , $min, $max, $documents] = self::SPENDING[$category];

        $amount = mt_rand($min, $max) * $inflation;
        $amount = mt_rand(1, 100) <= 60 ? max(990, floor($amount / 1000) * 1000 + 990) : max(500, round($amount, -1));

        $type = $this->pick($documents);
        $vendor = $this->pick($vendors);
        do {
            $number = (string) mt_rand(100_000, 9_999_999);
        } while (isset($this->docKeys["$type|$vendor|$number"]));
        $this->docKeys["$type|$vendor|$number"] = true;

        $date = Carbon::createFromTimestamp(mt_rand($from->timestamp, $to->copy()->startOfDay()->timestamp), $to->getTimezone());

        return [
            'category' => $category,
            'category_id' => $this->categoryIds[$category] ?? null,
            'vendor_name' => $vendor,
            'amount' => (int) $amount,
            'document_type' => $type,
            'document_number' => $number,
            'project' => mt_rand(1, 100) <= 80 ? $project : $this->pick(self::PROJECTS),
            'city' => mt_rand(1, 100) <= 80 ? $city : $this->pick(self::CITIES),
            'expense_date' => $date->toDateString(),
        ];
    }

    private function storePlanilla(int $expenseId, array $worker, array $items, int $total, Carbon $at, int $userId): void
    {
        $jpeg = $this->planillaImage($worker, $items, $total, $at);
        $path = "expenses/{$expenseId}/rendicion/planilla.jpg";
        Storage::disk(Document::DISK)->put($path, $jpeg);

        DB::table('documents')->insert([
            'name' => 'planilla.jpg',
            'file_path' => $path,
            'mime_type' => 'image/jpeg',
            'file_size' => strlen($jpeg),
            'document_type' => 'other',
            'expense_id' => $expenseId,
            'uploaded_by' => $userId,
            'is_enabled' => true,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    /** La planilla «Rendición de Gastos» llenada a mano, como la que mandan los trabajadores. */
    private function planillaImage(array $worker, array $items, int $total, Carbon $at): string
    {
        $im = imagecreatetruecolor(760, 980);
        imagefill($im, 0, 0, imagecolorallocate($im, 251, 250, 246));
        $line = imagecolorallocate($im, 150, 150, 155);
        $print = imagecolorallocate($im, 40, 40, 45);
        $ink = imagecolorallocate($im, 28, 52, 150);
        $ttf = function_exists('imagettftext') && is_file(self::FONT);
        $hand = $ttf && is_file(self::FONT_HAND) ? self::FONT_HAND : self::FONT;
        $text = function (string $value, int $x, int $y, int $size, $color, ?string $font = null, int $angle = 0) use ($im, $ttf) {
            $ttf ? imagettftext($im, $size, $angle, $x, $y, $color, $font ?? self::FONT, $value) : imagestring($im, 3, $x, $y - 12, Str::ascii($value), $color);
        };
        $amount = fn (int $value) => number_format($value, 0, ',', '.');

        $text('Rendición de Gastos', 285, 52, 16, $print, is_file(self::FONT_BOLD) ? self::FONT_BOLD : self::FONT);
        $columns = [40 => 'Fecha', 110 => 'Concepto', 245 => 'Proyecto', 390 => 'Ciudad', 500 => 'Valor (Factura)', 610 => 'Valor (Boleta)', 720 => null];
        imagerectangle($im, 40, 75, 720, 715, $line);
        foreach (array_keys($columns) as $x) {
            imageline($im, $x, 75, $x, 715, $line);
        }
        foreach ($columns as $x => $label) {
            if ($label) {
                $text($label, $x + 5, 95, 9, $print);
            }
        }
        for ($y = 105; $y <= 715; $y += 30) {
            imageline($im, 40, $y, 720, $y, $line);
        }

        $previousDate = null;
        foreach (array_slice($items, 0, 20) as $row => $item) {
            $y = 128 + $row * 30;
            $date = Carbon::parse($item['expense_date'])->format('d-m');
            $text($date === $previousDate ? '  "' : $date, 46, $y, 11, $ink, $hand, mt_rand(-2, 2));
            $previousDate = $date;
            $concept = ['Insumos y Materiales' => 'Materiales', 'Mantenimiento' => 'Mantención', 'Vulcanización' => 'Vulcaniz.'][$item['category']] ?? $item['category'];
            $text(Str::limit($concept, 14, ''), 116, $y, 11, $ink, $hand, mt_rand(-2, 2));
            $text(Str::limit($item['project'], 15, ''), 251, $y, 10, $ink, $hand, mt_rand(-2, 2));
            $text(Str::limit($item['city'], 13, ''), 395, $y, 9, $ink, $hand, mt_rand(-2, 2));
            $column = $item['document_type'] === 'factura' ? 506 : 616;
            $text($amount($item['amount']), $column, $y, 11, $ink, $hand, mt_rand(-2, 2));
            imageline($im, $item['document_type'] === 'factura' ? 625 : 515, $y - 6, $item['document_type'] === 'factura' ? 700 : 590, $y - 6, $ink);
        }
        $lastY = 105 + min(count($items), 20) * 30;
        if ($lastY < 715) {
            imageline($im, 40, 715, 720, $lastY, $ink);
        }

        $facturas = array_sum(array_map(fn ($i) => $i['document_type'] === 'factura' ? $i['amount'] : 0, $items));
        $text('Fecha Rendición', 120, 738, 8, $print);
        imagerectangle($im, 60, 742, 300, 772, $line);
        $text($at->format('d-m-y'), 130, 765, 13, $ink, $hand);
        $text('Sub total', 420, 745, 9, $print);
        imagerectangle($im, 500, 728, 720, 760, $line);
        imageline($im, 610, 728, 610, 760, $line);
        $text('$ ' . $amount($facturas), 506, 751, 12, $ink, $hand);
        $text('$ ' . $amount($total - $facturas), 616, 751, 12, $ink, $hand);
        $text('Total', 520, 790, 10, $print, is_file(self::FONT_BOLD) ? self::FONT_BOLD : self::FONT);
        imagerectangle($im, 570, 770, 720, 802, $line);
        $text('$ ' . $amount($total), 580, 794, 13, $ink, $hand);

        [$rutNumber, $rutDv] = explode('-', $worker['rut'] . '-');
        $text('RUT', 40, 850, 8, $print);
        $text(number_format((int) $rutNumber, 0, '', '.') . '-' . $rutDv, 70, 846, 12, $ink, $hand);
        imageline($im, 65, 852, 300, 852, $line);
        $text('Nombre y Firma', 330, 850, 8, $print);
        $text($worker['name'], 420, 846, 12, $ink, $hand);
        imageline($im, 415, 852, 720, 852, $line);
        for ($i = 0; $i < 3; $i++) {
            imagearc($im, 640 + $i * 18, 840, 60, 28 + $i * 6, 180 + $i * 20, 40 + $i * 30, $ink);
        }
        $text('RUT', 40, 920, 8, $print);
        imageline($im, 65, 922, 300, 922, $line);
        $text('Revisor y autorización — Nombre y Firma', 330, 920, 8, $print);
        imageline($im, 560, 922, 720, 922, $line);

        ob_start();
        imagejpeg($im, null, 68);
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

    private function nextNumber(string $table, string $column, string $prefix, int $year): string
    {
        $this->sequences[$prefix][$year] ??= (int) DB::table($table)->where($column, 'like', "{$prefix}-{$year}-%")
            ->pluck($column)->map(fn ($n) => (int) Str::afterLast($n, '-'))->max();

        return sprintf('%s-%d-%06d', $prefix, $year, ++$this->sequences[$prefix][$year]);
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
