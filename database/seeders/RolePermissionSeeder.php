<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'place orders',
            'view own orders',
            'view available fuels',
            'manage users',
            'manage fuels',
            'manage orders',
            'manage drivers',
            'manage own addresses',
            'view own notifications',
            'confirm deliveries',
            'view assigned deliveries',
            'update assigned delivery status',
            'update driver location',
            'record cash collections',
            'manage provider branches',
            'manage provider inventory',
            'manage provider drivers',
            'manage provider trucks',
            'manage provider orders',
            'assign provider deliveries',
            'manage providers',
            'manage provider accounts',
            'view all branches',
            'view all customers',
            'view all drivers',
            'view all orders',
            'view all payments',
            'manage platform users',
            'view platform reports',
            'manage platform settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $customer = Role::findOrCreate('customer', 'web');
        $driver = Role::findOrCreate('driver', 'web');
        $provider = Role::findOrCreate('provider', 'web');
        $admin = Role::findOrCreate('admin', 'web');

        $customer->givePermissionTo([
            'place orders',
            'view own orders',
            'view available fuels',
            'manage own addresses',
            'view own notifications',
            'confirm deliveries',
        ]);

        $driver->givePermissionTo([
            'view assigned deliveries',
            'update assigned delivery status',
            'update driver location',
            'record cash collections',
        ]);

        $provider->givePermissionTo([
            'manage provider branches',
            'manage provider inventory',
            'manage provider drivers',
            'manage provider trucks',
            'manage provider orders',
            'assign provider deliveries',
            'view own orders',
            'view own notifications',
        ]);

        $admin->givePermissionTo(Permission::query()->pluck('name')->all());
    }
}
