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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique(); // Número de rendición
            $table->foreignId('account_id')->constrained('accounts'); // Cuenta que rinde
            $table->foreignId('submitted_by')->constrained('people'); // Persona que envía la rendición
            $table->decimal('total_amount', 15, 2); // Monto total rendido
            $table->text('description'); // Descripción general
            $table->date('expense_date'); // Fecha del período rendido
            $table->enum('status', ['draft', 'submitted', 'reviewed', 'approved', 'rejected'])->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users'); // Usuario que revisó
            $table->timestamp('submitted_at')->nullable(); // Fecha de envío
            $table->timestamp('reviewed_at')->nullable(); // Fecha de revisión
            $table->text('rejection_reason')->nullable(); // Motivo de rechazo
            $table->boolean('is_enabled')->default(true); // Control de estado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
