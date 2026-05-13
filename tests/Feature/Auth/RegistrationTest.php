<?php

namespace Tests\Feature\Auth;

use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
});

test('new users can register', function () {
    $component = Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'Password1!')
        ->set('password_confirmation', 'Password1!');

    $component->call('register');

    $component->assertHasNoErrors();

    $this->assertAuthenticated();
});
