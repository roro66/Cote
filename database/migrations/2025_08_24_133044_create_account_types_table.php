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
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insertar tipos de cuentas
        $accountTypes = [
            [
                'name' => 'Cuenta Corriente',
                'description' => 'Cuenta para personas naturales y jurídicas con chequera'
            ],
            [
                'name' => 'Cuenta Vista',
                'description' => 'Cuenta de depósito a la vista sin chequera'
            ],
            [
                'name' => 'Cuenta de Ahorro',
                'description' => 'Cuenta de ahorro con rentabilidad'
            ],
            [
                'name' => 'Cuenta RUT',
                'description' => 'Cuenta básica asociada al RUT (BancoEstado)'
            ],
            [
                'name' => 'Chequera Electrónica',
                'description' => 'Cuenta corriente 100% digital'
            ],
        ];

        foreach ($accountTypes as $type) {
            DB::table('account_types')->insert([
                'name' => $type['name'],
                'description' => $type['description'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_types');
    }
};
