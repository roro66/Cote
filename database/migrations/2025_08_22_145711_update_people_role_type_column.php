<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero cambiar la columna a VARCHAR para poder almacenar los nuevos valores
        Schema::table('people', function (Blueprint $table) {
            $table->string('role_type', 50)->change();
        });

        // Ahora actualizar los valores existentes
        DB::table('people')->where('role_type', 'team_leader')->update(['role_type' => 'tesorero']);
        DB::table('people')->where('role_type', 'team_member')->update(['role_type' => 'trabajador']);
        DB::table('people')->where('role_type', 'supervisor')->update(['role_type' => 'tesorero']);
        DB::table('people')->where('role_type', 'admin')->update(['role_type' => 'tesorero']);

        // En PostgreSQL, aplicar restricción CHECK explícita para emular enum
        if (DB::getDriverName() === 'pgsql') {
            // Eliminar la restricción CHECK previa si existe
            DB::statement('ALTER TABLE people DROP CONSTRAINT IF EXISTS people_role_type_check');
            // Añadir la nueva restricción con los valores permitidos
            DB::statement("ALTER TABLE people ADD CONSTRAINT people_role_type_check CHECK (role_type IN ('tesorero','trabajador'))");
        } else {
            // En otros motores, mantener enum
            Schema::table('people', function (Blueprint $table) {
                $table->enum('role_type', ['tesorero', 'trabajador'])->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar la restricción/enum anterior
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE people DROP CONSTRAINT IF EXISTS people_role_type_check');
            DB::statement("ALTER TABLE people ADD CONSTRAINT people_role_type_check CHECK (role_type IN ('team_leader','team_member','supervisor','admin'))");
        } else {
            Schema::table('people', function (Blueprint $table) {
                $table->enum('role_type', ['team_leader', 'team_member', 'supervisor', 'admin'])->change();
            });
        }

        // Restaurar los valores anteriores
        DB::table('people')->where('role_type', 'tesorero')->update(['role_type' => 'team_leader']);
        DB::table('people')->where('role_type', 'trabajador')->update(['role_type' => 'team_member']);
    }
};
