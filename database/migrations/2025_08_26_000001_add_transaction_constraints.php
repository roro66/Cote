<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Solo para PostgreSQL
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // 1) Constraint: type solo 'transfer'
        DB::unprepared("ALTER TABLE transactions ADD CONSTRAINT chk_transactions_type CHECK (type = 'transfer');");

        // 2) Trigger function para validar que sea Tesorería <-> Persona
        DB::unprepared(<<<'SQL'
        CREATE OR REPLACE FUNCTION validate_transaction_accounts() RETURNS TRIGGER AS $$
        DECLARE
            from_type TEXT;
            to_type TEXT;
        BEGIN
            IF NEW.from_account_id IS NULL OR NEW.to_account_id IS NULL THEN
                RAISE EXCEPTION 'from_account_id y to_account_id no pueden ser nulos';
            END IF;

            SELECT type INTO from_type FROM accounts WHERE id = NEW.from_account_id;
            SELECT type INTO to_type FROM accounts WHERE id = NEW.to_account_id;

            IF from_type IS NULL OR to_type IS NULL THEN
                RAISE EXCEPTION 'Cuentas inválidas para la transacción';
            END IF;

            IF NOT ((from_type = 'treasury' AND to_type = 'person') OR (from_type = 'person' AND to_type = 'treasury')) THEN
                RAISE EXCEPTION 'Solo se permiten transferencias entre Tesorería y cuentas personales';
            END IF;

            RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared("DROP TRIGGER IF EXISTS trg_validate_transaction_accounts ON transactions;");
        DB::unprepared(<<<'SQL'
        CREATE TRIGGER trg_validate_transaction_accounts
        BEFORE INSERT OR UPDATE ON transactions
        FOR EACH ROW EXECUTE FUNCTION validate_transaction_accounts();
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        DB::unprepared("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS chk_transactions_type;");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_validate_transaction_accounts ON transactions;");
        DB::unprepared("DROP FUNCTION IF EXISTS validate_transaction_accounts();");
    }
};
