<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return; // Solo aplicamos estas garantías en PostgreSQL
        }

        // Normalizar datos antes de imponer restricciones
        DB::transaction(function () {
            $ids = DB::table('accounts')
                ->where('type', 'treasury')
                ->orderBy('id')
                ->pluck('id');

            if ($ids->count() > 1) {
                $keepId = $ids->first();
                DB::table('accounts')
                    ->where('type', 'treasury')
                    ->whereNotIn('id', [$keepId])
                    ->delete();
            }

            // Asegurar que Tesorería no tenga dueño
            DB::table('accounts')
                ->where('type', 'treasury')
                ->update(['person_id' => null]);

            // Crear índice único parcial: solo puede existir una fila con type='treasury'
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS accounts_one_treasury ON accounts (type) WHERE type = 'treasury'");

            // Restringir que Tesorería no tenga person_id
            DB::statement("ALTER TABLE accounts ADD CONSTRAINT accounts_treasury_person_null CHECK (NOT (type = 'treasury' AND person_id IS NOT NULL))");
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("DROP INDEX IF EXISTS accounts_one_treasury");
        DB::statement("ALTER TABLE accounts DROP CONSTRAINT IF EXISTS accounts_treasury_person_null");
    }
};
