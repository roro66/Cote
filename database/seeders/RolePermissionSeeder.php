<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos
        $permissions = [
            // Gestión de personas
            'people.view',
            'people.create',
            'people.edit',
            'people.delete',
            
            // Gestión de cuadrillas
            'teams.view',
            'teams.create',
            'teams.edit',
            'teams.delete',
            
            // Gestión de cuentas
            'accounts.view',
            'accounts.create',
            'accounts.edit',
            'accounts.delete',
            
            // Gestión de transacciones
            'transactions.view',
            'transactions.create',
            'transactions.edit',
            'transactions.approve',
            'transactions.delete',
            
            // Gestión de gastos
            'expenses.view',
            'expenses.create',
            'expenses.edit',
            'expenses.review',
            'expenses.approve',
            'expenses.delete',
            
            // Reportes
            'reports.view',
            'reports.export',
            
            // Configuración del sistema
            'system.configure',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::create(['name' => $permission]);
        }

        // Crear roles
        $boss = \Spatie\Permission\Models\Role::create(['name' => 'boss']);
        $treasurer = \Spatie\Permission\Models\Role::create(['name' => 'treasurer']);
        $teamLeader = \Spatie\Permission\Models\Role::create(['name' => 'team_leader']);
        $teamMember = \Spatie\Permission\Models\Role::create(['name' => 'team_member']);

        // Asignar permisos al jefe (todos los permisos)
        $boss->givePermissionTo(\Spatie\Permission\Models\Permission::all());

        // Asignar permisos al tesorero
        $treasurer->givePermissionTo([
            'people.view', 'people.create', 'people.edit',
            'teams.view', 'teams.create', 'teams.edit',
            'accounts.view', 'accounts.create', 'accounts.edit',
            'transactions.view', 'transactions.create', 'transactions.edit', 'transactions.approve',
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.review', 'expenses.approve',
            'reports.view', 'reports.export',
        ]);

        // Asignar permisos al jefe de cuadrilla
        $teamLeader->givePermissionTo([
            'people.view',
            'teams.view',
            'accounts.view',
            'transactions.view',
            'expenses.view', 'expenses.create', 'expenses.edit',
            'reports.view',
        ]);

        // Asignar permisos al miembro de cuadrilla
        $teamMember->givePermissionTo([
            'expenses.view', 'expenses.create',
        ]);
    }
}
