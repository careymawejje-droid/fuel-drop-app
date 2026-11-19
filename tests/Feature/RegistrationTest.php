<?php

use Database\Seeders\RolePermissionSeeder;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
})->skip(function () {
    return ! Features::enabled(Features::registration());
}, 'Registration support is not enabled.');

test('registration screen cannot be rendered if support is disabled', function () {
    $response = $this->get('/register');

    $response->assertStatus(404);
})->skip(function () {
    return Features::enabled(Features::registration());
}, 'Registration support is enabled.');

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '0700123456',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
    expect(auth()->user()->hasRole('customer'))->toBeTrue();
    $this->get(route('home'))->assertRedirect(route('customer.dashboard'));
})->skip(function () {
    return ! Features::enabled(Features::registration());
}, 'Registration support is not enabled.');
