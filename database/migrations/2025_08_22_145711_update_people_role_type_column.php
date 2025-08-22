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

        // Finalmente cambiar a ENUM con los nuevos valores
        Schema::table('people', function (Blueprint $table) {
            $table->enum('role_type', ['tesorero', 'trabajador'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar la columna anterior
        Schema::table('people', function (Blueprint $table) {
            $table->enum('role_type', ['team_leader', 'team_member', 'supervisor', 'admin'])->change();
        });

        // Restaurar los valores anteriores
        DB::table('people')->where('role_type', 'tesorero')->update(['role_type' => 'team_leader']);
        DB::table('people')->where('role_type', 'trabajador')->update(['role_type' => 'team_member']);
    }
};
