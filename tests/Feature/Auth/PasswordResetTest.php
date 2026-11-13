<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('password reset by email is not available', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->get('/forgot-password')->assertNotFound();
    $this->post('/forgot-password', ['email' => $user->email])->assertNotFound();
    $this->get('/reset-password/token')->assertNotFound();
    $this->post('/reset-password', [
        'token' => 'token',
        'email' => $user->email,
        'password' => 'nueva-clave-123',
        'password_confirmation' => 'nueva-clave-123',
    ])->assertNotFound();

    Notification::assertNothingSent();
});

test('login screen does not offer password recovery', function () {
    $this->get('/login')
        ->assertOk()
        ->assertDontSee('forgot-password');
});
