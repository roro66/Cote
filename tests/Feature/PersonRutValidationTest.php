<?php

use App\Helpers\RutHelper;
use App\Models\Person;

it('validates chilean RUT check digits, including K', function () {
    expect(RutHelper::validate('12.345.678-5'))->toBeTrue();
    expect(RutHelper::validate('12345678-5'))->toBeTrue();
    expect(RutHelper::validate('10.000.013-K'))->toBeTrue();
    expect(RutHelper::validate('10.000.013-k'))->toBeTrue();
    expect(RutHelper::validate('12.345.678-9'))->toBeFalse();
    expect(RutHelper::validate('abc'))->toBeFalse();
});

function person_payload(string $rut): array
{
    return [
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'rut' => $rut,
        'email' => 'juan.perez+' . uniqid() . '@cote.com',
        'role_type' => 'trabajador',
        'is_enabled' => 1,
    ];
}

it('rejects a person with an invalid RUT', function () {
    $this->actingAs(create_treasurer_user())
        ->postJson(route('people.store'), person_payload('12.345.678-9'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('rut');

    expect(Person::count())->toBe(0);
});

it('accepts a person whose RUT ends in K', function () {
    $this->actingAs(create_treasurer_user())
        ->post(route('people.store'), person_payload('10.000.013-K'))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('people.index'));

    expect(Person::sole()->rut_formatted)->toContain('10.000.013-K');
});
