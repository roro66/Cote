<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Person;

uses(RefreshDatabase::class);

it('orders people datatable by name asc when requested via AJAX', function () {
    // Crear personas usando atributos que existen en la tabla
    Person::create([
        'first_name' => 'Carlos',
        'last_name' => 'Perez',
        'rut' => '11111111-1',
        'role_type' => 'trabajador',
        'is_enabled' => true,
    ]);
    Person::create([
        'first_name' => 'Ana',
        'last_name' => 'Gonzalez',
        'rut' => '22222222-2',
        'role_type' => 'trabajador',
        'is_enabled' => true,
    ]);
    Person::create([
        'first_name' => 'Beatriz',
        'last_name' => 'Lopez',
        'rut' => '33333333-3',
        'role_type' => 'trabajador',
        'is_enabled' => true,
    ]);

    // Construir request como lo hace DataTables: order[0][column]=0&order[0][dir]=asc
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);

    // Llamar directamente al controlador para evitar middleware y variaciones de ruta
    $request = \Illuminate\Http\Request::create('/datatables/people', 'GET', [
        'order' => [['column' => 0, 'dir' => 'asc']],
        'start' => 0,
        'length' => 50,
        'search' => ['value' => 'Ana'],
    ]);
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');

    $controller = new \App\Http\Controllers\DataTables\PersonDataTableController();
    $response = $controller->index($request);

    $content = $response->getContent();
    $json = json_decode($content, true);

    // Aseguramos que el endpoint responde datos en formato DataTables
    expect(is_array($json))->toBeTrue();
    expect(array_key_exists('data', $json))->toBeTrue();
    expect(is_array($json['data']))->toBeTrue();
    expect(count($json['data']))->toBeGreaterThanOrEqual(1);
});
