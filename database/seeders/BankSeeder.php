<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bank;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            // Bancos principales
            ['name' => 'Banco de Chile', 'code' => '001', 'type' => 'banco'],
            ['name' => 'Banco Internacional', 'code' => '009', 'type' => 'banco'],
            ['name' => 'Scotiabank Chile', 'code' => '014', 'type' => 'banco'],
            ['name' => 'Banco de Crédito e Inversiones', 'code' => '016', 'type' => 'banco'],
            ['name' => 'Banco Bice', 'code' => '028', 'type' => 'banco'],
            ['name' => 'HSBC Bank (Chile)', 'code' => '031', 'type' => 'banco'],
            ['name' => 'Banco Santander Chile', 'code' => '037', 'type' => 'banco'],
            ['name' => 'Banco Itaú Chile', 'code' => '039', 'type' => 'banco'],
            ['name' => 'Banco Security', 'code' => '049', 'type' => 'banco'],
            ['name' => 'Banco Falabella', 'code' => '051', 'type' => 'banco'],
            ['name' => 'Deutsche Bank (Chile)', 'code' => '052', 'type' => 'banco'],
            ['name' => 'Banco Ripley', 'code' => '053', 'type' => 'banco'],
            ['name' => 'Rabobank Chile', 'code' => '054', 'type' => 'banco'],
            ['name' => 'Banco Consorcio', 'code' => '055', 'type' => 'banco'],
            ['name' => 'Banco Penta', 'code' => '056', 'type' => 'banco'],
            ['name' => 'Banco París', 'code' => '057', 'type' => 'banco'],
            ['name' => 'Banco BTG Pactual Chile', 'code' => '059', 'type' => 'banco'],
            ['name' => 'China Construction Bank', 'code' => '060', 'type' => 'banco'],

            // Cooperativas de ahorro y crédito
            ['name' => 'Coopeuch', 'code' => 'COOP001', 'type' => 'cooperativa'],
            ['name' => 'Detacoop', 'code' => 'COOP002', 'type' => 'cooperativa'],
            ['name' => 'Oriencoop', 'code' => 'COOP003', 'type' => 'cooperativa'],
            ['name' => 'Capual', 'code' => 'COOP004', 'type' => 'cooperativa'],
            ['name' => 'Ahorrocoop', 'code' => 'COOP005', 'type' => 'cooperativa'],

            // Tarjetas prepago y billeteras digitales
            ['name' => 'Multicaja', 'code' => 'PREP001', 'type' => 'tarjeta_prepago'],
            ['name' => 'TenpoCard', 'code' => 'PREP002', 'type' => 'tarjeta_prepago'],
            ['name' => 'Mach (BCI)', 'code' => 'PREP003', 'type' => 'tarjeta_prepago'],
            ['name' => 'Junaeb', 'code' => 'PREP004', 'type' => 'tarjeta_prepago'],
            ['name' => 'Fintual', 'code' => 'PREP005', 'type' => 'tarjeta_prepago'],
            ['name' => 'Klap', 'code' => 'PREP006', 'type' => 'tarjeta_prepago'],

            // Otros
            ['name' => 'Banco del Estado de Chile', 'code' => '012', 'type' => 'banco'],
            ['name' => 'Banco Corpbanca', 'code' => '027', 'type' => 'banco'],
            ['name' => 'Banco do Brasil S.A.', 'code' => '017', 'type' => 'banco'],
        ];

        foreach ($banks as $bank) {
            Bank::firstOrCreate(
                ['code' => $bank['code']], // Campo único para verificar
                [
                    'name' => $bank['name'],
                    'type' => $bank['type'],
                    'is_active' => true,
                ]
            );
        }
    }
}
