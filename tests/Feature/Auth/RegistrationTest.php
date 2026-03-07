<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
});

test('new users can register', function () {
    $component = Volt::test('pages.auth.register')
        ->set('shop_name', 'Harbour Lane Goods')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('registering creates a shop owned by the new user with a first location', function () {
    Volt::test('pages.auth.register')
        ->set('shop_name', 'Harbour Lane Goods')
        ->set('currency', 'EUR')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->role)->toBe(Role::Owner)
        ->and($user->shop->name)->toBe('Harbour Lane Goods')
        ->and($user->shop->currency)->toBe('EUR')
        ->and($user->shop->locations()->sole()->is_default)->toBeTrue();
});
