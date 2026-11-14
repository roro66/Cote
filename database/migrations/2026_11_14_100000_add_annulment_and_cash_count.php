<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movimientos anulados (estado «annulled», con quién, cuándo y por qué) y el detalle del
 * conteo de billetes al cerrar un mes.
 */
return new class extends Migration
{
    private const STATUSES = ['pending', 'approved', 'rejected', 'completed', 'annulled'];

    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('annulled_at')->nullable();
            $table->foreignId('annulled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('annul_reason', 500)->nullable();
        });

        $this->allowStatuses(self::STATUSES);

        Schema::table('period_closings', function (Blueprint $table) {
            $table->json('count_detail')->nullable();
        });
    }

    public function down(): void
    {
        DB::table('transactions')->where('status', 'annulled')->update(['status' => 'rejected']);
        $this->allowStatuses(['pending', 'approved', 'rejected', 'completed']);

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('annulled_by');
            $table->dropColumn(['annulled_at', 'annul_reason']);
        });

        Schema::table('period_closings', function (Blueprint $table) {
            $table->dropColumn('count_detail');
        });
    }

    /** @param  list<string>  $statuses */
    private function allowStatuses(array $statuses): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            // SQLite reconstruye la tabla: la columna queda como texto, sin la lista fija de la creación.
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('status', 20)->default('pending')->change();
            });

            return;
        }

        // El enum de Laravel en PostgreSQL es un CHECK; se reemplaza cualquiera que limite status.
        $checks = DB::select("
            SELECT conname FROM pg_constraint
            WHERE conrelid = 'transactions'::regclass AND contype = 'c' AND pg_get_constraintdef(oid) ILIKE '%status%'
        ");
        foreach ($checks as $check) {
            DB::statement('ALTER TABLE transactions DROP CONSTRAINT "'.str_replace('"', '""', $check->conname).'"');
        }
        $list = implode(', ', array_map(fn ($s) => "'{$s}'", $statuses));
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_status_check CHECK (status IN ({$list}))");
    }
};
