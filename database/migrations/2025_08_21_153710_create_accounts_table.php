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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre de la cuenta
            $table->enum('type', ['treasury', 'person']); // Tipo de cuenta (removido 'team')
            $table->foreignId('person_id')->nullable()->constrained('people'); // Persona responsable
            $table->decimal('balance', 15, 2)->default(0); // Saldo actual
            $table->text('notes')->nullable(); // Notas adicionales
            $table->boolean('is_enabled')->default(true); // Control de estado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
