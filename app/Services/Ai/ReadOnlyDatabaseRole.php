<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Usuario de PostgreSQL con el que el asistente ejecuta sus SELECT: solo lectura y solo sobre
 * las tablas y columnas permitidas. Lo que este usuario no ve, el asistente no lo puede leer
 * aunque el modelo arme un SQL que esquive los filtros de ReadOnlySql.
 *
 * Crear el usuario exige CREATEROLE (en producción lo crea el administrador de PostgreSQL una vez).
 * Los permisos sobre las tablas los da el dueño de las tablas después de cada migrate.
 */
class ReadOnlyDatabaseRole
{
    public function name(): string
    {
        return (string) config('ai.sql_role', 'coteso_ai');
    }

    public function sync(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $role = $this->name();
        $quotedRole = $this->identifier($role);

        if ($this->canManageRoles()) {
            if (! DB::selectOne('SELECT 1 AS ok FROM pg_roles WHERE rolname = ?', [$role])) {
                DB::statement("CREATE ROLE {$quotedRole} NOLOGIN");
            }
            DB::statement("ALTER ROLE {$quotedRole} NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS");
            $password = (string) config('database.connections.pgsql_ai.password');
            if ($password !== '') {
                DB::statement("ALTER ROLE {$quotedRole} LOGIN PASSWORD ".DB::getPdo()->quote($password));
            }
            DB::statement("ALTER ROLE {$quotedRole} SET default_transaction_read_only = on");
            DB::statement("ALTER ROLE {$quotedRole} SET statement_timeout = '15s'");
        }

        if (! DB::selectOne('SELECT 1 AS ok FROM pg_roles WHERE rolname = ?', [$role])) {
            return;
        }

        try {
            DB::statement("GRANT USAGE ON SCHEMA public TO {$quotedRole}");
            DB::statement("REVOKE ALL ON ALL TABLES IN SCHEMA public FROM {$quotedRole}");
            DB::statement("REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM {$quotedRole}");

            foreach ($this->readableTables() as $table) {
                $columns = ReadOnlySql::LIMITED_COLUMNS[$table] ?? null;
                $columnList = $columns === null
                    ? ''
                    : ' ('.implode(', ', array_map(fn ($c) => $this->identifier($c), $columns)).')';
                DB::statement("GRANT SELECT{$columnList} ON TABLE public.{$this->identifier($table)} TO {$quotedRole}");
            }
        } catch (Throwable $e) {
            Log::warning('Asistente IA: no se pudieron dar los permisos de solo lectura', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @return list<string>
     */
    public function readableTables(): array
    {
        $denied = array_flip(ReadOnlySql::DENIED_TABLES);

        return collect(DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tableowner = current_user ORDER BY tablename"))
            ->pluck('tablename')
            ->reject(fn ($table) => isset($denied[$table]))
            ->values()
            ->all();
    }

    private function canManageRoles(): bool
    {
        $me = DB::selectOne('SELECT rolsuper, rolcreaterole FROM pg_roles WHERE rolname = current_user');

        return $me && ($me->rolsuper || $me->rolcreaterole);
    }

    private function identifier(string $name): string
    {
        return '"'.str_replace('"', '""', $name).'"';
    }
}
