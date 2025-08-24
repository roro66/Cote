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
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['banco', 'tarjeta_prepago', 'cooperativa']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insertar todos los bancos de Chile
        $banks = [
            // Bancos principales
            ['name' => 'Banco de Chile', 'code' => 'BCH', 'type' => 'banco'],
            ['name' => 'BancoEstado', 'code' => 'BE', 'type' => 'banco'],
            ['name' => 'Banco Santander', 'code' => 'SAN', 'type' => 'banco'],
            ['name' => 'Banco de Crédito e Inversiones (BCI)', 'code' => 'BCI', 'type' => 'banco'],
            ['name' => 'Banco Security', 'code' => 'SEC', 'type' => 'banco'],
            ['name' => 'Scotiabank Chile', 'code' => 'SCO', 'type' => 'banco'],
            ['name' => 'Banco Itaú Chile', 'code' => 'ITA', 'type' => 'banco'],
            ['name' => 'Banco BICE', 'code' => 'BIC', 'type' => 'banco'],
            ['name' => 'HSBC Bank Chile', 'code' => 'HSB', 'type' => 'banco'],
            ['name' => 'Banco Consorcio', 'code' => 'CON', 'type' => 'banco'],
            ['name' => 'Banco Ripley', 'code' => 'RIP', 'type' => 'banco'],
            ['name' => 'Banco Falabella', 'code' => 'FAL', 'type' => 'banco'],
            ['name' => 'Banco Paris', 'code' => 'PAR', 'type' => 'banco'],
            ['name' => 'Banco Internacional', 'code' => 'INT', 'type' => 'banco'],
            ['name' => 'Banco BTG Pactual Chile', 'code' => 'BTG', 'type' => 'banco'],
            ['name' => 'Banco do Brasil', 'code' => 'BDB', 'type' => 'banco'],
            ['name' => 'JP Morgan Chase Bank', 'code' => 'JPM', 'type' => 'banco'],
            ['name' => 'Banco Penta', 'code' => 'PEN', 'type' => 'banco'],
            
            // Cooperativas
            ['name' => 'Coopeuch', 'code' => 'CPE', 'type' => 'cooperativa'],
            ['name' => 'Capredena', 'code' => 'CAP', 'type' => 'cooperativa'],
            ['name' => 'Dipreca', 'code' => 'DIP', 'type' => 'cooperativa'],
            ['name' => 'CorpBanca', 'code' => 'COR', 'type' => 'cooperativa'],
            
            // Tarjetas de prepago y fintech
            ['name' => 'Multicaja', 'code' => 'MUL', 'type' => 'tarjeta_prepago'],
            ['name' => 'TenpoCard', 'code' => 'TEN', 'type' => 'tarjeta_prepago'],
            ['name' => 'Mach (BCI)', 'code' => 'MAC', 'type' => 'tarjeta_prepago'],
            ['name' => 'Chek (Banco de Chile)', 'code' => 'CHE', 'type' => 'tarjeta_prepago'],
            ['name' => 'Klap (Banco Security)', 'code' => 'KLA', 'type' => 'tarjeta_prepago'],
            ['name' => 'Junaeb (Tarjeta Nacional Estudiantil)', 'code' => 'TNE', 'type' => 'tarjeta_prepago'],
            ['name' => 'Sencillito (BancoEstado)', 'code' => 'SEN', 'type' => 'tarjeta_prepago'],
            ['name' => 'Prepago Los Héroes', 'code' => 'HER', 'type' => 'tarjeta_prepago'],
        ];

        foreach ($banks as $bank) {
            DB::table('banks')->insert([
                'name' => $bank['name'],
                'code' => $bank['code'],
                'type' => $bank['type'],
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
        Schema::dropIfExists('banks');
    }
};
