<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create unique partial index only if not present and there are no duplicates already stored
        DB::unprepared(<<<SQL
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_indexes WHERE indexname = 'expense_items_unique_doc_combo'
            ) THEN
                -- Check for existing duplicates (normalized)
                IF NOT EXISTS (
                    SELECT 1 FROM (
                        SELECT document_type,
                               lower(trim(vendor_name)) AS vendor_norm,
                               lower(trim(document_number)) AS number_norm,
                               COUNT(*) AS c
                        FROM expense_items
                        WHERE document_number IS NOT NULL
                        GROUP BY document_type, vendor_norm, number_norm
                        HAVING COUNT(*) > 1
                    ) dup
                ) THEN
                    CREATE UNIQUE INDEX expense_items_unique_doc_combo
                    ON expense_items (
                        document_type,
                        lower(trim(vendor_name)),
                        lower(trim(document_number))
                    )
                    WHERE document_number IS NOT NULL;
                END IF;
            END IF;
        END
        $$;
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS expense_items_unique_doc_combo');
    }
};
