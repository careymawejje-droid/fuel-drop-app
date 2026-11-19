<?php

use App\Models\Provider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('seeds only the four supported role names', function () {
    expect(Role::query()->orderBy('name')->pluck('name')->all())
        ->toBe(['admin', 'customer', 'driver', 'provider']);
});

it('merges legacy role assignments and permissions without detaching provider links', function () {
    $legacyAdminPermission = Permission::create(['name' => 'preserved admin permission', 'guard_name' => 'web']);
    $legacyProviderPermission = Permission::create(['name' => 'preserved provider permission', 'guard_name' => 'web']);
    $administratorRole = Role::create(['name' => 'administrator', 'guard_name' => 'web']);
    $superAdminRole = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $providerAdminRole = Role::create(['name' => 'provider_admin', 'guard_name' => 'web']);
    $administratorRole->givePermissionTo($legacyAdminPermission);
    $superAdminRole->givePermissionTo('manage users');
    $providerAdminRole->givePermissionTo($legacyProviderPermission);

    $adminUser = User::factory()->create()->assignRole('admin');
    $adminUser->assignRole($administratorRole);
    $adminUser->assignRole($superAdminRole);
    $providerUser = User::factory()->create()->assignRole($providerAdminRole);
    $provider = Provider::create([
        'admin_user_id' => $providerUser->id,
        'name' => 'Legacy Provider',
        'status' => 'active',
    ]);
    $provider->users()->attach($providerUser->id, ['is_active' => true]);

    $migration = require database_path('migrations/2026_11_12_111424_migrate_legacy_fuel_drop_roles.php');
    $migration->up();
    $this->seed(RolePermissionSeeder::class);

    $adminUser->refresh();
    $providerUser->refresh();
    $adminRole = Role::findByName('admin', 'web');
    $providerRole = Role::findByName('provider', 'web');

    expect($adminUser->roles()->where('name', 'admin')->count())->toBe(1)
        ->and($adminUser->hasRole('administrator'))->toBeFalse()
        ->and($providerUser->hasRole('provider'))->toBeTrue()
        ->and($adminRole->permissions()->whereKey($legacyAdminPermission->id)->exists())->toBeTrue()
        ->and($providerRole->permissions()->whereKey($legacyProviderPermission->id)->exists())->toBeTrue()
        ->and($providerUser->providers()->whereKey($provider->id)->wherePivot('is_active', true)->exists())->toBeTrue()
        ->and(Role::query()->count())->toBe(4);
});

it('refuses to migrate unsupported roles without deleting them', function () {
    $unsupportedRole = Role::create(['name' => 'custom_legacy_role', 'guard_name' => 'web']);
    $migration = require database_path('migrations/2026_11_12_111424_migrate_legacy_fuel_drop_roles.php');

    expect(fn () => $migration->up())
        ->toThrow(RuntimeException::class, 'unsupported web roles');

    $this->assertModelExists($unsupportedRole);
});
