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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique(); // Número de transacción
            $table->enum('type', ['transfer', 'payment', 'adjustment']); // Tipo de transacción
            $table->foreignId('from_account_id')->constrained('accounts'); // Cuenta origen
            $table->foreignId('to_account_id')->constrained('accounts'); // Cuenta destino
            $table->decimal('amount', 15, 2); // Monto
            $table->text('description'); // Descripción
            $table->text('notes')->nullable(); // Notas adicionales
            $table->foreignId('created_by')->constrained('users'); // Usuario que creó la transacción
            $table->foreignId('approved_by')->nullable()->constrained('users'); // Usuario que aprobó
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->timestamp('approved_at')->nullable(); // Fecha de aprobación
            $table->boolean('is_enabled')->default(true); // Control de estado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
