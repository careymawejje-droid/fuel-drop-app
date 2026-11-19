<?php

use App\Models\Address;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Provider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role as PermissionRole;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('requires the customer permissions to place orders and manage owned addresses', function () {
    $customer = User::factory()->create()->assignRole('customer');
    $address = Address::create([
        'user_id' => $customer->id,
        'label' => 'Home',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);

    expect($customer->can('create', Address::class))
        ->toBeTrue()
        ->and($customer->can('update', $address))
        ->toBeTrue();

    $this->actingAs($customer)
        ->get(route('customer.orders.create'))
        ->assertOk();
    $this->get(route('customer.orders.index'))->assertOk();
    $this->get(route('notifications.index'))->assertOk();

    PermissionRole::findByName('customer', 'web')->revokePermissionTo('view available fuels');
    $this->get(route('customer.orders.create'))->assertForbidden();
    $this->get(route('customer.dashboard'))
        ->assertOk()
        ->assertDontSee('Order fuel');

    PermissionRole::findByName('customer', 'web')->revokePermissionTo('place orders');
    PermissionRole::findByName('customer', 'web')->revokePermissionTo('manage own addresses');

    $this->post(route('customer.orders.store'), [])->assertForbidden();

    expect($customer->fresh()->can('create', Address::class))
        ->toBeFalse()
        ->and($customer->fresh()->can('update', $address))
        ->toBeFalse();

    PermissionRole::findByName('customer', 'web')->revokePermissionTo('view own orders');
    $this->get(route('customer.orders.index'))->assertForbidden();

    PermissionRole::findByName('customer', 'web')->revokePermissionTo('view own notifications');
    $this->get(route('notifications.index'))->assertForbidden();

    $this->assertDatabaseCount('orders', 0);
});

it('requires the provider management permission for branch operations', function () {
    $providerUser = User::factory()->create()->assignRole('provider');
    $provider = Provider::create([
        'admin_user_id' => $providerUser->id,
        'name' => 'Permission Provider',
        'status' => 'active',
    ]);
    $provider->users()->attach($providerUser->id, ['is_active' => true]);

    PermissionRole::findByName('provider', 'web')->revokePermissionTo('manage provider branches');

    $this->actingAs($providerUser)
        ->get(route('provider.branches.create', $provider))
        ->assertForbidden();

    $this->get(route('provider.providers.fuels.index', $provider))->assertOk();

    PermissionRole::findByName('provider', 'web')->revokePermissionTo('manage provider inventory');

    $this->get(route('provider.providers.fuels.index', $provider))->assertForbidden();
    $this->get(route('provider.providers.show', $provider))
        ->assertOk()
        ->assertDontSee('Manage branches')
        ->assertDontSee('Manage inventory');
});

it('requires the driver delivery status permission before changing an order', function () {
    $driverUser = User::factory()->create()->assignRole('driver');
    $driver = Driver::create(['user_id' => $driverUser->id]);
    $order = Order::create([
        'order_number' => 'FD-PERMISSION-DRIVER',
        'user_id' => $driverUser->id,
        'driver_id' => $driver->id,
        'total' => 10000,
        'delivery_address' => 'Kampala',
        'status' => 'assigned',
    ]);

    PermissionRole::findByName('driver', 'web')->revokePermissionTo('update assigned delivery status');

    $this->actingAs($driverUser)
        ->post(route('driver.deliveries.status', $order), ['status' => 'out_for_delivery'])
        ->assertForbidden();

    $this->get(route('driver.deliveries.show', $order))
        ->assertOk()
        ->assertDontSee('Start delivery');

    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'assigned']);
});

it('requires the platform settings permission before changing settings', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Provider::create([
        'name' => 'Unassigned Provider',
        'status' => 'active',
    ]);

    PermissionRole::findByName('admin', 'web')->revokePermissionTo('manage platform settings');
    PermissionRole::findByName('admin', 'web')->revokePermissionTo('view all payments');
    PermissionRole::findByName('admin', 'web')->revokePermissionTo('manage provider accounts');

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'contact_email' => 'help@example.com',
            'delivery_fee' => 1000,
        ])
        ->assertForbidden();

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Platform settings')
        ->assertDontSee('Payments');
    $this->get(route('admin.providers.index'))
        ->assertOk()
        ->assertDontSee('Create account');

    $this->assertDatabaseMissing('platform_settings', ['setting_key' => 'platform']);
});

it('stores driver locations only for a driver with the location permission', function () {
    $driverUser = User::factory()->create()->assignRole('driver');
    $driver = Driver::create(['user_id' => $driverUser->id]);

    $response = $this->actingAs($driverUser)
        ->postJson(route('driver.location.update'), [
            'latitude' => 0.3476,
            'longitude' => 32.5825,
            'accuracy_metres' => 12.5,
        ]);

    $response->assertCreated()
        ->assertJsonPath('location.driver_id', $driver->id);

    $this->assertDatabaseHas('driver_locations', [
        'driver_id' => $driver->id,
        'latitude' => 0.3476,
        'longitude' => 32.5825,
        'accuracy_metres' => 12.5,
    ]);
    $this->assertDatabaseHas('drivers', [
        'id' => $driver->id,
        'current_latitude' => 0.3476,
        'current_longitude' => 32.5825,
    ]);

    PermissionRole::findByName('driver', 'web')->revokePermissionTo('update driver location');

    $this->postJson(route('driver.location.update'), [
        'latitude' => 0.5,
        'longitude' => 32.6,
    ])->assertForbidden();

    $this->assertDatabaseCount('driver_locations', 1);
});
