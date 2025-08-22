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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del archivo
            $table->string('file_path'); // Ruta del archivo
            $table->string('mime_type'); // Tipo MIME
            $table->integer('file_size'); // Tamaño en bytes
            $table->enum('document_type', ['boleta', 'factura', 'guia_despacho', 'ticket', 'vale', 'other']);
            $table->foreignId('expense_item_id')->nullable()->constrained('expense_items'); // Item de gasto asociado
            $table->foreignId('uploaded_by')->constrained('users'); // Usuario que subió
            $table->boolean('is_enabled')->default(true); // Control de estado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
