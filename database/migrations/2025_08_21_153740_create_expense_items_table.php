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
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->onDelete('cascade');
            $table->enum('document_type', ['boleta', 'factura', 'guia_despacho', 'ticket', 'vale']); // Tipo de documento
            $table->string('document_number')->nullable(); // Número del documento
            $table->string('vendor_name'); // Nombre del proveedor
            $table->text('description'); // Descripción del gasto
            $table->decimal('amount', 15, 2); // Monto del item
            $table->date('expense_date'); // Fecha del gasto
            $table->string('category')->nullable(); // Categoría (materiales, peajes, almuerzo, etc.)
            $table->boolean('is_enabled')->default(true); // Control de estado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_items');
    }
};
