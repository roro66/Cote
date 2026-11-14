<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rendiciones recibidas en papel (planilla + boletas pegadas) que el tesorero transcribe:
 * origen y total escrito en la planilla, proyecto y ciudad de cada gasto, y fotos de la
 * rendición completa (documentos ligados a la rendición en vez de a un gasto).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('source', 20)->default('app');
            $table->decimal('declared_total', 15, 2)->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('expense_items', function (Blueprint $table) {
            $table->string('project', 150)->nullable();
            $table->string('city', 100)->nullable();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_id');
        });

        Schema::table('expense_items', function (Blueprint $table) {
            $table->dropColumn(['project', 'city']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registered_by');
            $table->dropColumn(['source', 'declared_total']);
        });
    }
};
