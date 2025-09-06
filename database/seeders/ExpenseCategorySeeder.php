<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'PEA', 'name' => 'Peaje', 'description' => 'Gastos en peajes de vehículos'],
            ['code' => 'ALI', 'name' => 'Alimentación', 'description' => 'Comidas, viandas, refrigerios'],
            ['code' => 'VUL', 'name' => 'Vulcanización', 'description' => 'Reparación de neumáticos y servicios relacionados'],
            ['code' => 'INS', 'name' => 'Insumos y Materiales', 'description' => 'Materiales menores, suministros'],
            ['code' => 'HER', 'name' => 'Herramientas', 'description' => 'Compra o reparación de herramientas'],
            ['code' => 'COM', 'name' => 'Combustible', 'description' => 'Gasolina, diésel, etc.'],
            ['code' => 'HOS', 'name' => 'Hospedaje', 'description' => 'Hoteles y alojamiento'],
            ['code' => 'VIA', 'name' => 'Viáticos', 'description' => 'Gastos de viaje y movilidad'],
            ['code' => 'SER', 'name' => 'Servicios', 'description' => 'Servicios contratados (electricidad, agua, etc.)'],
            ['code' => 'MTC', 'name' => 'Mantenimiento', 'description' => 'Mantenimiento de equipos y vehículos'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::updateOrCreate(['code' => $cat['code']], array_merge($cat, ['is_enabled' => true]));
        }
    }
}
