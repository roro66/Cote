<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_closings', function (Blueprint $table) {
            $table->id();
            $table->date('period')->unique(); // primer día del mes cerrado
            $table->foreignId('closed_by')->constrained('users');
            $table->timestamp('closed_at');
            $table->decimal('treasury_counted', 15, 2)->nullable(); // arqueo: dinero contado en Tesorería
            $table->text('notes')->nullable();
            $table->json('snapshot'); // resumen por cuenta al momento del cierre
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_closings');
    }
};
