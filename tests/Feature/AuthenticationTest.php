<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create()->assignRole('customer');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
    $this->get(route('home'))->assertRedirect(route('customer.dashboard'));
});

test('users are redirected to the dashboard for their role', function (string $role, string $dashboardRoute) {
    $user = User::factory()->create()->assignRole($role);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route($dashboardRoute));
})->with([
    'provider' => ['provider', 'provider.dashboard'],
    'admin' => ['admin', 'admin.dashboard'],
    'driver' => ['driver', 'driver.dashboard'],
    'customer' => ['customer', 'customer.dashboard'],
]);

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});
