<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ingreso a caja chica (type = deposit): sin cuenta origen y solo hacia Tesorería.
 * Las transferencias siguen siendo Tesorería <-> cuenta personal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('from_account_id')->nullable()->change();
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS chk_transactions_type;');
        DB::unprepared("ALTER TABLE transactions ADD CONSTRAINT chk_transactions_type CHECK (type IN ('transfer', 'deposit'));");

        DB::unprepared(<<<'SQL'
        CREATE OR REPLACE FUNCTION validate_transaction_accounts() RETURNS TRIGGER AS $$
        DECLARE
            from_type TEXT;
            to_type TEXT;
        BEGIN
            SELECT type INTO to_type FROM accounts WHERE id = NEW.to_account_id;
            IF to_type IS NULL THEN
                RAISE EXCEPTION 'Cuenta destino inválida para la transacción';
            END IF;

            IF NEW.type = 'deposit' THEN
                IF NEW.from_account_id IS NOT NULL OR to_type <> 'treasury' THEN
                    RAISE EXCEPTION 'Un ingreso a caja chica no tiene cuenta origen y solo puede ir a Tesorería';
                END IF;
                RETURN NEW;
            END IF;

            SELECT type INTO from_type FROM accounts WHERE id = NEW.from_account_id;
            IF from_type IS NULL THEN
                RAISE EXCEPTION 'Cuenta origen inválida para la transacción';
            END IF;

            IF NOT ((from_type = 'treasury' AND to_type = 'person') OR (from_type = 'person' AND to_type = 'treasury')) THEN
                RAISE EXCEPTION 'Solo se permiten transferencias entre Tesorería y cuentas personales';
            END IF;

            RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS chk_transactions_type;');
            DB::unprepared("ALTER TABLE transactions ADD CONSTRAINT chk_transactions_type CHECK (type = 'transfer');");
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('from_account_id')->nullable(false)->change();
        });
    }
};
