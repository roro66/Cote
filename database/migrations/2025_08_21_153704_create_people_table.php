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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('first_name'); // Nombre
            $table->string('last_name'); // Apellido
            $table->string('rut')->unique(); // RUT chileno
            $table->string('email')->nullable(); // Email
            $table->string('phone')->nullable(); // Teléfono
            $table->string('bank_name')->nullable(); // Banco
            $table->string('account_type')->nullable(); // Tipo de cuenta (corriente, vista, etc.)
            $table->string('account_number')->nullable(); // Número de cuenta
            $table->text('address')->nullable(); // Dirección
            $table->enum('role_type', ['boss', 'treasurer', 'team_leader', 'team_member']); // Tipo de rol
            $table->boolean('is_enabled')->default(true); // Control de estado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
