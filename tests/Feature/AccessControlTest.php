<?php

use App\Models\Address;
use App\Models\Branch;
use App\Models\BranchFuel;
use App\Models\Driver;
use App\Models\Fuel;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Truck;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Jetstream;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('public registration always creates a customer even when a privileged role is submitted', function () {
    $this->post('/register', [
        'name' => 'Public Customer',
        'email' => 'public.customer@example.com',
        'phone' => '0700123456',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ])->assertRedirect(route('home', absolute: false));

    $user = User::query()->where('email', 'public.customer@example.com')->firstOrFail();

    expect($user->hasRole('customer'))->toBeTrue()
        ->and($user->hasAnyRole(['driver', 'provider', 'admin']))->toBeFalse();
});

test('a driver can only access assigned deliveries', function () {
    $driverUser = User::factory()->create()->assignRole('driver');
    $otherDriverUser = User::factory()->create()->assignRole('driver');
    $driver = Driver::create(['user_id' => $driverUser->id]);
    $otherDriver = Driver::create(['user_id' => $otherDriverUser->id]);
    $customer = User::factory()->create()->assignRole('customer');

    $assignedOrder = Order::create([
        'order_number' => 'FD-DRIVER-1',
        'user_id' => $customer->id,
        'driver_id' => $driver->id,
        'total' => 10000,
        'delivery_address' => 'Kampala',
    ]);
    $otherOrder = Order::create([
        'order_number' => 'FD-DRIVER-2',
        'user_id' => $customer->id,
        'driver_id' => $otherDriver->id,
        'total' => 10000,
        'delivery_address' => 'Kampala',
    ]);

    $this->actingAs($driverUser)
        ->get(route('driver.deliveries.show', $assignedOrder))
        ->assertOk();

    $this->actingAs($driverUser)
        ->get(route('driver.deliveries.show', $otherOrder))
        ->assertForbidden();
});

test('a provider admin cannot access another provider resources', function () {
    $providerOne = Provider::create(['name' => 'Provider One']);
    $providerTwo = Provider::create(['name' => 'Provider Two']);
    $providerAdmin = User::factory()->create()->assignRole('provider');
    $providerOne->users()->attach($providerAdmin->id, ['is_active' => true]);

    $branch = Branch::create([
        'provider_id' => $providerTwo->id,
        'name' => 'Branch Two',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);
    $fuel = Fuel::create(['name' => 'Petrol Test', 'price_per_litre' => 5000]);
    $inventory = BranchFuel::create([
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 5000,
    ]);
    $driver = Driver::create(['user_id' => User::factory()->create()->id, 'provider_id' => $providerTwo->id]);
    $truck = Truck::create(['provider_id' => $providerTwo->id, 'name' => 'Truck Two', 'plate' => 'UAB 002B']);
    $order = Order::create([
        'order_number' => 'FD-PROVIDER-2',
        'user_id' => User::factory()->create()->id,
        'provider_id' => $providerTwo->id,
        'total' => 10000,
        'delivery_address' => 'Kampala',
    ]);

    $this->actingAs($providerAdmin)
        ->get(route('provider.providers.show', $providerOne))
        ->assertOk();

    $this->actingAs($providerAdmin)
        ->get(route('provider.providers.show', $providerTwo))
        ->assertForbidden();
    $this->get(route('provider.providers.orders.show', [$providerOne, $order]))
        ->assertNotFound();

    expect($providerAdmin->can('view', $branch))->toBeFalse()
        ->and($providerAdmin->can('view', $inventory))->toBeFalse()
        ->and($providerAdmin->can('view', $driver))->toBeFalse()
        ->and($providerAdmin->can('view', $truck))->toBeFalse()
        ->and($providerAdmin->can('view', $order))->toBeFalse();
});

test('an admin can access every provider', function () {
    $providerOne = Provider::create(['name' => 'Provider One']);
    $providerTwo = Provider::create(['name' => 'Provider Two']);
    $superAdmin = User::factory()->create()->assignRole('admin');

    $this->actingAs($superAdmin)
        ->get(route('provider.providers.show', $providerOne))
        ->assertOk();

    $this->actingAs($superAdmin)
        ->get(route('provider.providers.show', $providerTwo))
        ->assertOk();

    $this->actingAs($superAdmin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Providers');

    $this->actingAs($superAdmin)
        ->get(route('provider.providers.index'))
        ->assertOk()
        ->assertSee('All providers');
});

test('a customer cannot access another customer order or address', function () {
    $customer = User::factory()->create()->assignRole('customer');
    $otherCustomer = User::factory()->create()->assignRole('customer');
    $order = Order::create([
        'order_number' => 'FD-CUSTOMER-2',
        'user_id' => $otherCustomer->id,
        'total' => 10000,
        'delivery_address' => 'Kampala',
    ]);
    $address = Address::create([
        'user_id' => $otherCustomer->id,
        'label' => 'Home',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);

    $this->actingAs($customer)
        ->get(route('orders.show', $order))
        ->assertForbidden();

    expect($customer->can('view', $address))->toBeFalse();
});

test('customers and guests cannot access administrative routes', function () {
    $customer = User::factory()->create()->assignRole('customer');

    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));

    $this->actingAs($customer)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('a provider cannot create or manage provider admin accounts', function () {
    $providerAdmin = User::factory()->create()->assignRole('provider');
    $provider = Provider::create(['name' => 'Assigned Provider', 'status' => 'active']);
    $provider->users()->attach($providerAdmin->id, ['is_active' => true]);

    $this->actingAs($providerAdmin)
        ->get(route('admin.providers.accounts.create', $provider))
        ->assertForbidden();

    $this->post(route('admin.providers.accounts.store', $provider), [
        'name' => 'Unauthorized Admin',
        'email' => 'unauthorized.admin@example.com',
        'password' => 'provider-password',
        'password_confirmation' => 'provider-password',
    ])->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'unauthorized.admin@example.com']);
});

test('customers see only their own order history', function () {
    $customer = User::factory()->create()->assignRole('customer');
    $otherCustomer = User::factory()->create()->assignRole('customer');

    Order::create([
        'order_number' => 'FD-OWN-HISTORY',
        'user_id' => $customer->id,
        'total' => 10000,
        'delivery_address' => 'Kampala',
    ]);
    Order::create([
        'order_number' => 'FD-OTHER-HISTORY',
        'user_id' => $otherCustomer->id,
        'total' => 20000,
        'delivery_address' => 'Entebbe',
    ]);

    $this->actingAs($customer)
        ->get(route('customer.orders.index'))
        ->assertOk()
        ->assertSee('FD-OWN-HISTORY')
        ->assertDontSee('FD-OTHER-HISTORY');
});

test('customers can review fuel line items saved on their order', function () {
    $customer = User::factory()->create()->assignRole('customer');
    $order = Order::create([
        'order_number' => 'FD-ORDER-LINES',
        'user_id' => $customer->id,
        'total' => 100000,
        'delivery_address' => 'Kampala',
    ]);
    $fuel = Fuel::create(['name' => 'Petrol Line Item', 'price_per_litre' => 5000]);
    $order->fuels()->attach($fuel->id, [
        'quantity_litres' => 20,
        'unit_price' => 5000,
    ]);

    $this->actingAs($customer)
        ->get(route('customer.orders.show', $order))
        ->assertOk()
        ->assertSee('Petrol Line Item')
        ->assertSee('20 L')
        ->assertSee('UGX 100,000');
});

test('drivers see only deliveries assigned to them', function () {
    $driverUser = User::factory()->create()->assignRole('driver');
    $otherDriverUser = User::factory()->create()->assignRole('driver');
    $driver = Driver::create(['user_id' => $driverUser->id]);
    $otherDriver = Driver::create(['user_id' => $otherDriverUser->id]);
    $customer = User::factory()->create()->assignRole('customer');

    Order::create([
        'order_number' => 'FD-OWN-DELIVERY',
        'user_id' => $customer->id,
        'driver_id' => $driver->id,
        'total' => 10000,
        'delivery_address' => 'Kampala',
    ]);
    Order::create([
        'order_number' => 'FD-OTHER-DELIVERY',
        'user_id' => $customer->id,
        'driver_id' => $otherDriver->id,
        'total' => 20000,
        'delivery_address' => 'Entebbe',
    ]);

    $this->actingAs($driverUser)
        ->get(route('driver.deliveries.index'))
        ->assertOk()
        ->assertSee('FD-OWN-DELIVERY')
        ->assertDontSee('FD-OTHER-DELIVERY');
});

test('provider admins see only drivers belonging to an accessible provider', function () {
    $provider = Provider::create(['name' => 'Assigned Provider']);
    $otherProvider = Provider::create(['name' => 'Other Provider']);
    $providerAdmin = User::factory()->create()->assignRole('provider');
    $provider->users()->attach($providerAdmin->id, ['is_active' => true]);
    $assignedDriver = User::factory()->create()->assignRole('driver');
    $otherDriver = User::factory()->create()->assignRole('driver');

    Driver::create([
        'user_id' => $assignedDriver->id,
        'provider_id' => $provider->id,
        'truck_plate' => 'UAA 101A',
    ]);
    Driver::create([
        'user_id' => $otherDriver->id,
        'provider_id' => $otherProvider->id,
        'truck_plate' => 'UAA 102A',
    ]);

    $this->actingAs($providerAdmin)
        ->get(route('provider.providers.drivers.index', $provider))
        ->assertOk()
        ->assertSee($assignedDriver->name)
        ->assertDontSee($otherDriver->name);

    $this->get(route('provider.providers.drivers.index', $otherProvider))
        ->assertForbidden();
});

test('provider admins see only orders belonging to accessible providers on their dashboard', function () {
    $provider = Provider::create(['name' => 'Dashboard Provider']);
    $otherProvider = Provider::create(['name' => 'Hidden Provider']);
    $providerAdmin = User::factory()->create()->assignRole('provider');
    $provider->users()->attach($providerAdmin->id, ['is_active' => true]);
    $customer = User::factory()->create()->assignRole('customer');

    Order::create([
        'order_number' => 'FD-DASHBOARD-OWN',
        'user_id' => $customer->id,
        'provider_id' => $provider->id,
        'total' => 10000,
        'delivery_address' => 'Kampala',
    ]);
    Order::create([
        'order_number' => 'FD-DASHBOARD-HIDDEN',
        'user_id' => $customer->id,
        'provider_id' => $otherProvider->id,
        'total' => 20000,
        'delivery_address' => 'Entebbe',
    ]);
    Order::create([
        'order_number' => 'FD-DASHBOARD-ACTIVE',
        'user_id' => $customer->id,
        'provider_id' => $provider->id,
        'total' => 15000,
        'delivery_address' => 'Kampala',
        'status' => 'fuel_loading',
    ]);

    $response = $this->actingAs($providerAdmin)
        ->get(route('provider.dashboard'));

    $response
        ->assertOk()
        ->assertSee('FD-DASHBOARD-OWN')
        ->assertDontSee('FD-DASHBOARD-HIDDEN')
        ->assertSee('Incoming orders')
        ->assertSee('Active orders')
        ->assertSee('Provider workspaces')
        ->assertSee('Fuel inventory')
        ->assertViewHas('stats', fn (array $stats): bool => $stats['incoming_orders'] === 1
            && $stats['active_orders'] === 1);
});

test('database seeding creates local role logins and a linked sample delivery', function () {
    config([
        'fueldrop.admin.email' => null,
        'fueldrop.admin.password' => null,
    ]);

    $this->seed(DatabaseSeeder::class);

    $roleAccounts = [
        'admin@gmail.com' => 'admin',
        'admin@fueldrop.test' => 'provider',
        'driver1@gmail.com' => 'driver',
        'driver2@gmail.com' => 'driver',
        'customer1@gmail.com' => 'customer',
    ];

    foreach ($roleAccounts as $email => $role) {
        $user = User::query()->where('email', $email)->firstOrFail();

        expect($user->hasRole($role))->toBeTrue()
            ->and(Hash::check('password', $user->password))->toBeTrue();
    }

    $provider = Provider::query()->where('name', 'FuelDrop Demo Provider')->firstOrFail();
    $providerAdmin = User::query()->where('email', 'admin@fueldrop.test')->firstOrFail();
    $driverUser = User::query()->where('email', 'driver1@gmail.com')->firstOrFail();
    $deliveryOrder = Order::query()->where('order_number', 'FDDEL234')->firstOrFail();
    $secondDriverUser = User::query()->where('email', 'driver2@gmail.com')->firstOrFail();
    $secondDeliveryOrder = Order::query()->where('order_number', 'FDFUE234')->firstOrFail();
    $fuel = Fuel::query()->where('name', 'Petrol')->firstOrFail();
    $branchFuel = BranchFuel::query()
        ->where('branch_id', $deliveryOrder->branch_id)
        ->where('fuel_id', $fuel->id)
        ->firstOrFail();

    expect($provider->admin_user_id)->toBe($providerAdmin->id)
        ->and($providerAdmin->providers()->whereKey($provider->id)->exists())->toBeTrue()
        ->and($deliveryOrder->provider_id)->toBe($provider->id)
        ->and($deliveryOrder->driver?->user_id)->toBe($driverUser->id)
        ->and($secondDeliveryOrder->driver?->user_id)->toBe($secondDriverUser->id);

    $this->assertDatabaseHas('fuel_order', [
        'order_id' => $deliveryOrder->id,
        'fuel_id' => $fuel->id,
        'branch_fuel_id' => $branchFuel->id,
        'quantity_litres' => 20,
    ]);
});

test('database seeding does not create demo accounts in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--force' => true,
    ])->assertSuccessful();

    $this->assertDatabaseMissing('users', ['email' => 'admin@fueldrop.test']);
    $this->assertDatabaseMissing('users', ['email' => 'driver1@gmail.com']);
    $this->assertDatabaseMissing('users', ['email' => 'customer1@gmail.com']);
    $this->assertDatabaseMissing('orders', ['order_number' => 'FDDEL234']);
});
