<?php

use App\Support\ErrorMessage;

it('shows business rule messages to the user', function () {
    expect(ErrorMessage::for(new DomainException('Fondos insuficientes.'), 'Error al aprobar'))
        ->toBe('Fondos insuficientes.');
});

it('hides internal error details from the user', function () {
    $message = ErrorMessage::for(new RuntimeException('SQLSTATE[23505]: duplicate key secreto'), 'Error al guardar');

    expect($message)->toBe('Error al guardar. Intenta de nuevo o contacta al administrador.')
        ->not->toContain('SQLSTATE');
});
