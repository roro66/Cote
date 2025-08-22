<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Person;
use App\Helpers\RutHelper;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate valid Chilean RUTs
        $sampleRuts = RutHelper::generateSampleRuts();
        
        // Create sample people first
        $people = [
            [
                'first_name' => 'Carlos',
                'last_name' => 'Mendoza',
                'rut' => $sampleRuts[0], // Valid Chilean RUT
                'email' => 'carlos.mendoza@coteso.com',
                'phone' => '987654321',
                'role_type' => 'team_leader',
                'is_enabled' => true,
            ],
            [
                'first_name' => 'María',
                'last_name' => 'García',
                'rut' => $sampleRuts[1], // Valid Chilean RUT
                'email' => 'maria.garcia@coteso.com',
                'phone' => '987654322',
                'role_type' => 'team_member',
                'is_enabled' => true,
            ],
            [
                'first_name' => 'Luis',
                'last_name' => 'Rodríguez',
                'rut' => $sampleRuts[2], // Valid Chilean RUT
                'email' => 'luis.rodriguez@coteso.com',
                'phone' => '987654323',
                'role_type' => 'team_member',
                'is_enabled' => true,
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'López',
                'rut' => $sampleRuts[3], // Valid Chilean RUT
                'email' => 'ana.lopez@coteso.com',
                'phone' => '987654324',
                'role_type' => 'team_leader',
                'is_enabled' => true,
            ],
        ];

        foreach ($people as $personData) {
            Person::firstOrCreate(
                ['rut' => $personData['rut']], // Unique field to check
                $personData // Data to create if not exists
            );
        }

        // Create sample teams with leaders
        $teams = [
            [
                'name' => 'Cuadrilla Norte',
                'description' => 'Equipo de trabajo para proyectos en la zona norte',
                'leader_id' => 1, // Carlos Mendoza
                'is_enabled' => true,
            ],
            [
                'name' => 'Cuadrilla Sur',
                'description' => 'Equipo de trabajo para proyectos en la zona sur',
                'leader_id' => 4, // Ana López
                'is_enabled' => true,
            ],
            [
                'name' => 'Cuadrilla Centro',
                'description' => 'Equipo de trabajo para proyectos en la zona centro',
                'leader_id' => 1, // Carlos Mendoza
                'is_enabled' => true,
            ],
            [
                'name' => 'Equipo Mantenimiento',
                'description' => 'Equipo especializado en mantenimiento de equipos',
                'leader_id' => 4, // Ana López
                'is_enabled' => true,
            ],
        ];

        foreach ($teams as $teamData) {
            $team = Team::firstOrCreate(
                ['name' => $teamData['name']], // Unique field to check
                $teamData // Data to create if not exists
            );
            
            // Assign people to teams (only if not already assigned)
            if ($team->members()->count() == 0) {
                $team->members()->attach([1, 2]); // Assign first 2 people to each team
            }
        }
    }
}
