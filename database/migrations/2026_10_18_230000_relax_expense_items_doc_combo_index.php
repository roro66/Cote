<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El índice único impedía volver a rendir la boleta de una rendición rechazada o eliminada.
 * La unicidad se valida en ExpenseController considerando solo rendiciones vigentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS expense_items_unique_doc_combo');

        Schema::table('expense_items', function (Blueprint $table) {
            $table->index(['document_type', 'document_number'], 'expense_items_doc_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('expense_items', function (Blueprint $table) {
            $table->dropIndex('expense_items_doc_lookup');
        });
    }
};
