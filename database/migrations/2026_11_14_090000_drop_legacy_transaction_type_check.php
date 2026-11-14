<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El enum original de type dejó en PostgreSQL el CHECK transactions_type_check
 * (transfer, payment, adjustment), que convivía con chk_transactions_type
 * (transfer, deposit) e impedía guardar ingresos a caja chica.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check');
    }

    public function down(): void
    {
        // No se restaura: volvería a bloquear los ingresos a caja chica.
    }
};
