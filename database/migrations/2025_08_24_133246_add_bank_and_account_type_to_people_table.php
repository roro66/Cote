<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            // Cambiar las columnas de string a foreign keys
            $table->dropColumn(['bank_name', 'account_type']);
            
            // Agregar las nuevas columnas como foreign keys
            $table->foreignId('bank_id')->nullable()->constrained('banks')->onDelete('set null');
            $table->foreignId('account_type_id')->nullable()->constrained('account_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            // Eliminar las foreign keys
            $table->dropForeign(['bank_id']);
            $table->dropForeign(['account_type_id']);
            $table->dropColumn(['bank_id', 'account_type_id']);
            
            // Restaurar las columnas originales
            $table->string('bank_name')->nullable();
            $table->string('account_type')->nullable();
        });
    }
};
