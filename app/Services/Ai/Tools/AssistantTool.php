<?php

namespace App\Services\Ai\Tools;

use App\Models\Person;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Consulta fija y de solo lectura que el modelo puede elegir. El modelo solo completa
 * los parámetros; la consulta vive aquí. Para un trabajador, toda consulta se limita a su persona.
 */
abstract class AssistantTool
{
    abstract public function name(): string;

    abstract public function description(): string;

    /** @return array<string, mixed> JSON Schema de los parámetros */
    abstract public function parameters(): array;

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    abstract public function run(array $args): array;

    /** Solo Jefe y Tesorero pueden usarla. */
    public function staffOnly(): bool
    {
        return false;
    }

    public function availableTo(?User $user): bool
    {
        return $user !== null && (! $this->staffOnly() || $this->isStaff($user));
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => $this->description(),
                'parameters' => $this->parameters(),
            ],
        ];
    }

    protected function user(): ?User
    {
        return auth()->user();
    }

    protected function isStaff(?User $user = null): bool
    {
        $user ??= $this->user();

        return $user !== null && $user->hasAnyRole(['boss', 'treasurer']);
    }

    /**
     * Personas que calzan con el texto. Un trabajador solo puede consultarse a sí mismo.
     *
     * @return array{0: Collection<int, Person>|null, 1: array<string, mixed>|null}
     */
    protected function people(?string $text): array
    {
        if (! $this->isStaff()) {
            $own = Person::whereKey($this->user()?->person_id ?? 0)->get();

            return $own->isEmpty()
                ? [null, ['ok' => false, 'mensaje' => 'Tu usuario no está vinculado a una persona. Pídele al Jefe que lo vincule en Usuarios.']]
                : [$own, null];
        }

        $text = trim((string) $text);
        if ($text === '') {
            return [null, null];
        }

        $tokens = array_filter(preg_split('/\s+/', $this->fold($text)) ?: [], fn ($t) => $t !== '');
        $matches = Person::orderBy('first_name')->orderBy('last_name')->get()
            ->filter(function (Person $person) use ($tokens) {
                $name = $this->fold($person->full_name);
                foreach ($tokens as $token) {
                    if (! str_contains($name, $token)) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        if ($matches->isEmpty()) {
            return [null, ['ok' => false, 'mensaje' => "No encontré a ninguna persona llamada «{$text}»."]];
        }
        if ($matches->count() > 8) {
            return [null, [
                'ok' => false,
                'mensaje' => "«{$text}» coincide con muchas personas. Pide el nombre y apellido.",
                'candidatos' => $matches->take(8)->map->full_name->all(),
            ]];
        }

        return [$matches, null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string}
     */
    protected function period(array $args, int $defaultMonths = 3): array
    {
        $tz = 'America/Santiago';
        $to = ! empty($args['hasta'])
            ? CarbonImmutable::parse((string) $args['hasta'], $tz)->endOfDay()
            : CarbonImmutable::now($tz)->endOfDay();

        if (! empty($args['desde'])) {
            $from = CarbonImmutable::parse((string) $args['desde'], $tz)->startOfDay();
        } else {
            $months = max(1, min(60, (int) ($args['meses'] ?? $defaultMonths)));
            $from = $to->subMonthsNoOverflow($months)->addDay()->startOfDay();
        }

        return [$from, $to, $from->format('d/m/Y').' al '.$to->format('d/m/Y')];
    }

    /** @return array<string, mixed> */
    protected function periodParameters(string $default): array
    {
        return [
            'desde' => ['type' => 'string', 'description' => 'Fecha inicial AAAA-MM-DD (opcional)'],
            'hasta' => ['type' => 'string', 'description' => 'Fecha final AAAA-MM-DD (opcional, por defecto hoy)'],
            'meses' => ['type' => 'integer', 'description' => "Meses hacia atrás si no hay fechas ({$default})"],
        ];
    }

    protected function money(float|int|string|null $value): float
    {
        return round((float) $value, 0);
    }

    protected function fold(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }
}
