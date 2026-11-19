<?php

use App\Mail\ContactMessage;
use App\Models\Branch;
use App\Models\BranchFuel;
use App\Models\Driver;
use App\Models\Fuel;
use App\Models\Order;
use App\Models\Provider;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('sends a validated contact message to the configured support address', function () {
    Mail::fake();

    $this->get(route('contact'))->assertOk()->assertSee('Contact us');

    $response = $this->post(route('contact.store'), [
        'name' => 'A Customer',
        'email' => 'customer@example.com',
        'message' => 'Please contact me about fuel delivery.',
    ]);

    $response->assertRedirect(route('contact'));
    Mail::assertSent(ContactMessage::class, fn (ContactMessage $message): bool => $message->hasTo(config('fueldrop.contact_email'))
        && $message->hasReplyTo('customer@example.com', 'A Customer')
        && $message->messageBody === 'Please contact me about fuel delivery.');
});

it('creates an order using the selected provider branch price and cash on delivery', function () {
    $customer = User::factory()->create()->assignRole('customer');
    $providerAdmin = User::factory()->create()->assignRole('provider');
    $provider = Provider::create([
        'name' => 'Order Provider',
        'admin_user_id' => $providerAdmin->id,
        'status' => 'active',
    ]);
    $provider->users()->attach($providerAdmin->id, ['is_active' => true]);
    $branch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Central Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
        'status' => 'active',
    ]);
    $fuel = Fuel::create([
        'name' => 'Petrol',
        'price_per_litre' => 5000,
        'status' => 'available',
    ]);
    $branchFuel = BranchFuel::create([
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 5000,
        'available_stock_litres' => 8,
        'reserved_stock_litres' => 0,
        'status' => 'active',
    ]);

    $this->actingAs($customer)->get(route('customer.orders.create'))
        ->assertOk()
        ->assertSee('Order Provider')
        ->assertSee('District')
        ->assertSee('Use my current location')
        ->assertSee('Pin your delivery point');

    $response = $this->actingAs($customer)->post(route('customer.orders.store'), [
        'provider_id' => $provider->id,
        'fuel_id' => $fuel->id,
        'quantity' => '3.5',
        'district' => 'Kampala',
        'locality' => 'Kampala City',
        'area' => 'Central Division',
        'road' => 'Kampala Road',
        'landmark' => 'Near the clock tower',
        'latitude' => '0.3476000',
        'longitude' => '32.5825000',
        'location_confirmed' => '1',
    ]);

    $response->assertSessionHasNoErrors();
    $order = Order::query()->firstOrFail();
    $response->assertRedirect(route('customer.orders.confirmation', $order, absolute: false));
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'user_id' => $customer->id,
        'provider_id' => $provider->id,
        'branch_id' => $branch->id,
        'status' => 'pending',
        'total' => 17500,
        'delivery_address' => 'Uganda, District: Kampala, Town/City: Kampala City, Area: Central Division, Road: Kampala Road, Landmark/Directions: Near the clock tower',
    ]);
    expect($order->order_number)->toMatch('/\AFD[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}\z/')
        ->and(strlen($order->order_number))->toBe(8)
        ->and(Order::query()->findOrFail($order->id)->user_id)->toBe($customer->id);

    $this->actingAs($customer)->get(route('customer.orders.index', ['q' => $order->order_number]))
        ->assertOk()->assertSee($order->order_number);
    $this->actingAs($providerAdmin)->get(route('provider.providers.orders.index', [$provider, 'q' => $order->order_number]))
        ->assertOk()->assertSee($order->order_number);
    $admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($admin)->get(route('admin.orders.index', ['q' => $order->order_number]))
        ->assertOk()->assertSee($order->order_number);
    expect((float) $order->delivery_latitude)->toBe(0.3476)
        ->and((float) $order->delivery_longitude)->toBe(32.5825);
    $this->actingAs($customer)->getJson(route('customer.orders.tracking', $order))
        ->assertOk()
        ->assertJsonPath('destination.latitude', 0.3476)
        ->assertJsonPath('destination.longitude', 32.5825);

    $this->actingAs($customer)->from(route('customer.orders.create'))->post(route('customer.orders.store'), [
        'provider_id' => $provider->id,
        'fuel_id' => $fuel->id,
        'quantity' => 3,
        'district' => 'Wakiso',
        'area' => 'Kiwatule',
        'latitude' => '0.3476000',
    ])->assertRedirect(route('customer.orders.create', absolute: false))
        ->assertSessionHasErrors(['longitude', 'location_confirmed']);

    foreach ([
        ['latitude' => '0', 'longitude' => '0'],
        ['latitude' => '32.5825', 'longitude' => '0.3476'],
        ['latitude' => '0.3476', 'longitude' => '0.3476'],
    ] as $invalidCoordinates) {
        $this->actingAs($customer)->from(route('customer.orders.create'))->post(route('customer.orders.store'), array_merge([
            'provider_id' => $provider->id,
            'fuel_id' => $fuel->id,
            'quantity' => '3',
            'district' => 'Kampala',
            'latitude' => '0.3476',
            'longitude' => '32.5825',
            'location_confirmed' => '1',
        ], $invalidCoordinates))->assertRedirect(route('customer.orders.create', absolute: false))
            ->assertSessionHasErrors('latitude');
    }
    $this->assertDatabaseCount('orders', 1);
    $this->assertDatabaseHas('fuel_order', [
        'order_id' => $order->id,
        'fuel_id' => $fuel->id,
        'branch_fuel_id' => $branchFuel->id,
    ]);

    expect((float) $order->fuels()->firstOrFail()->pivot->quantity_litres)->toBe(3.5)
        ->and((float) $order->payment()->firstOrFail()->amount)->toBe(17500.0);

    $this->assertDatabaseHas('payments', [
        'order_id' => $order->id,
        'payment_method' => 'cash_on_delivery',
        'status' => 'pending',
    ]);
    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'status' => 'pending',
        'changed_by' => $customer->id,
    ]);
    $this->assertDatabaseHas('user_notifications', [
        'user_id' => $providerAdmin->id,
        'order_id' => $order->id,
        'title' => 'New order awaiting provider response',
    ]);
});

it('rejects a customer order when the selected provider lacks enough stock', function () {
    $customer = User::factory()->create()->assignRole('customer');
    $provider = Provider::create(['name' => 'Low Stock Provider', 'status' => 'active']);
    $branch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Low Stock Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);
    $fuel = Fuel::create(['name' => 'Petrol', 'price_per_litre' => 5000, 'status' => 'available']);
    BranchFuel::create([
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 5000,
        'available_stock_litres' => 2,
        'status' => 'active',
    ]);

    $response = $this->actingAs($customer)->from(route('customer.orders.create'))->post(route('customer.orders.store'), [
        'provider_id' => $provider->id,
        'fuel_id' => $fuel->id,
        'quantity' => 3,
        'address' => 'Kampala Road',
        'latitude' => '0.3476000',
        'longitude' => '32.5825000',
        'location_confirmed' => '1',
    ]);

    $response->assertRedirect(route('customer.orders.create', absolute: false))
        ->assertSessionHasErrors('provider_id');
    $this->assertDatabaseCount('orders', 0);
});

it('prevents a customer from placing a second active order', function () {
    $customer = User::factory()->create()->assignRole('customer');
    $provider = Provider::create(['name' => 'Active Order Provider', 'status' => 'active']);
    $branch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Active Order Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);
    $fuel = Fuel::create(['name' => 'Petrol', 'price_per_litre' => 5000, 'status' => 'available']);
    BranchFuel::create([
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 5000,
        'available_stock_litres' => 8,
        'status' => 'active',
    ]);
    Order::create([
        'order_number' => 'FD-EXISTING-ACTIVE',
        'user_id' => $customer->id,
        'provider_id' => $provider->id,
        'branch_id' => $branch->id,
        'total' => 15000,
        'delivery_address' => 'Kampala',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($customer)->from(route('customer.orders.create'))->post(route('customer.orders.store'), [
        'provider_id' => $provider->id,
        'fuel_id' => $fuel->id,
        'quantity' => 3,
        'address' => 'Kampala Road',
        'latitude' => '0.3476000',
        'longitude' => '32.5825000',
        'location_confirmed' => '1',
    ]);

    $response->assertRedirect(route('customer.orders.create', absolute: false))
        ->assertSessionHasErrors('provider_id');
    $this->assertDatabaseCount('orders', 1);
});

it('renders customer order entry and detail pages for the owner', function () {
    $customer = User::factory()->create()->assignRole('customer');
    $provider = Provider::create(['name' => 'Page Provider', 'status' => 'active']);
    $branch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Page Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);
    $fuel = Fuel::create(['name' => 'Diesel', 'price_per_litre' => 4200, 'status' => 'available']);
    BranchFuel::create([
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 4200,
        'available_stock_litres' => 10,
        'status' => 'active',
    ]);
    $order = Order::create([
        'order_number' => 'FD-PAGE-ORDER',
        'user_id' => $customer->id,
        'provider_id' => $provider->id,
        'branch_id' => $branch->id,
        'total' => 12600,
        'delivery_address' => 'Kampala Road',
        'status' => 'pending',
    ]);

    $this->actingAs($customer)
        ->get(route('customer.orders.create'))
        ->assertOk()
        ->assertSee('Page Provider')
        ->assertSee('name="quantity"', false);

    $this->get(route('customer.orders.confirmation', $order))
        ->assertOk()
        ->assertSee('FD-PAGE-ORDER');

    $this->get(route('customer.orders.payment', $order))
        ->assertOk()
        ->assertSee('Cash on Delivery');
});

it('creates branch fuel inventory for an active branch', function () {
    $superAdmin = User::factory()->create()->assignRole('admin');
    $provider = Provider::create(['name' => 'Inventory Provider', 'status' => 'active']);
    $branch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Inventory Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);
    $fuel = Fuel::create(['name' => 'Petrol', 'price_per_litre' => 5000, 'status' => 'available']);

    $response = $this->actingAs($superAdmin)->post(route('provider.providers.fuels.store', $provider), [
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => '5100',
        'available_stock_litres' => '15.500',
        'status' => 'active',
    ]);

    $response->assertRedirect(route('provider.providers.fuels.index', $provider, absolute: false));
    $this->assertDatabaseHas('branch_fuel', [
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 5100,
        'available_stock_litres' => 15.5,
        'status' => 'active',
    ]);
});

it('rejects duplicate fuel inventory for the same branch', function () {
    $superAdmin = User::factory()->create()->assignRole('admin');
    $provider = Provider::create(['name' => 'Duplicate Inventory Provider', 'status' => 'active']);
    $branch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Duplicate Inventory Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);
    $fuel = Fuel::create(['name' => 'Diesel', 'price_per_litre' => 4200, 'status' => 'available']);
    BranchFuel::create([
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 4200,
        'available_stock_litres' => 10,
        'status' => 'active',
    ]);

    $response = $this->actingAs($superAdmin)->post(route('provider.providers.fuels.store', $provider), [
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => '4300',
        'available_stock_litres' => '12',
        'status' => 'active',
    ]);

    $response->assertInvalid([
        'fuel_id' => 'This branch already has an inventory entry for that fuel.',
    ]);
    $this->assertDatabaseCount('branch_fuel', 1);
});

it('rejects inventory creation for a branch owned by another provider', function () {
    $superAdmin = User::factory()->create()->assignRole('admin');
    $provider = Provider::create(['name' => 'Scoped Inventory Provider', 'status' => 'active']);
    $otherProvider = Provider::create(['name' => 'Other Inventory Provider', 'status' => 'active']);
    $otherBranch = Branch::create([
        'provider_id' => $otherProvider->id,
        'name' => 'Other Provider Branch',
        'address' => 'Entebbe',
        'latitude' => 0.0512,
        'longitude' => 32.4637,
    ]);
    $fuel = Fuel::create(['name' => 'Petrol', 'price_per_litre' => 5000, 'status' => 'available']);

    $response = $this->actingAs($superAdmin)->post(route('provider.providers.fuels.store', $provider), [
        'branch_id' => $otherBranch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => '5100',
        'available_stock_litres' => '10',
        'status' => 'active',
    ]);

    $response->assertInvalid(['branch_id' => 'The selected branch id is invalid.']);
    $this->assertDatabaseCount('branch_fuel', 0);
});

it('updates branch inventory only for the authorized provider', function () {
    $providerAdmin = User::factory()->create()->assignRole('provider');
    $provider = Provider::create(['name' => 'Managed Provider', 'status' => 'active']);
    $provider->users()->attach($providerAdmin->id, ['is_active' => true]);
    $branch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Managed Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);
    $fuel = Fuel::create(['name' => 'Diesel', 'price_per_litre' => 4200, 'status' => 'available']);
    $branchFuel = BranchFuel::create([
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 4200,
        'available_stock_litres' => 20,
        'reserved_stock_litres' => 2,
        'status' => 'active',
    ]);

    $this->actingAs($providerAdmin)
        ->put(route('provider.providers.fuels.update', [$provider, $branchFuel]), [
            'price_per_litre' => '4300',
            'available_stock_litres' => '18.500',
            'status' => 'active',
        ])
        ->assertRedirect(route('provider.providers.fuels.index', $provider, absolute: false));

    $this->assertDatabaseHas('branch_fuel', [
        'id' => $branchFuel->id,
        'price_per_litre' => 4300,
        'available_stock_litres' => 18.5,
        'reserved_stock_litres' => 2,
    ]);
});

it('renders the Admin user management pages', function () {
    $superAdmin = User::factory()->create()->assignRole('admin');
    $customer = User::factory()->create()->assignRole('customer');

    $this->actingAs($superAdmin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee($customer->email);

    $this->get(route('admin.users.create'))
        ->assertOk()
        ->assertSee('Add customer');

    $this->get(route('admin.users.edit', $customer))
        ->assertOk()
        ->assertSee($customer->email);
});

it('prevents a Provider account from editing another provider inventory', function () {
    $providerAdmin = User::factory()->create()->assignRole('provider');
    $assignedProvider = Provider::create(['name' => 'Assigned Provider', 'status' => 'active']);
    $assignedProvider->users()->attach($providerAdmin->id, ['is_active' => true]);
    $otherProvider = Provider::create(['name' => 'Other Provider', 'status' => 'active']);
    $branch = Branch::create([
        'provider_id' => $otherProvider->id,
        'name' => 'Other Branch',
        'address' => 'Entebbe',
        'latitude' => 0.0512,
        'longitude' => 32.4637,
    ]);
    $fuel = Fuel::create(['name' => 'Petrol', 'price_per_litre' => 5000, 'status' => 'available']);
    $branchFuel = BranchFuel::create([
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 5000,
        'available_stock_litres' => 10,
        'status' => 'active',
    ]);

    $this->actingAs($providerAdmin)
        ->get(route('provider.providers.fuels.edit', [$otherProvider, $branchFuel]))
        ->assertForbidden();
});

it('renders provider inventory and driver edit pages for an authorized super admin', function () {
    $superAdmin = User::factory()->create()->assignRole('admin');
    $provider = Provider::create(['name' => 'Page Admin Provider', 'status' => 'active']);
    $branch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Admin Page Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);
    $fuel = Fuel::create(['name' => 'Diesel', 'price_per_litre' => 4200, 'status' => 'available']);
    $branchFuel = BranchFuel::create([
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 4200,
        'available_stock_litres' => 10,
        'status' => 'active',
    ]);
    $driverUser = User::factory()->create()->assignRole('driver');
    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'provider_id' => $provider->id,
        'branch_id' => $branch->id,
        'truck_plate' => 'UAA 777A',
    ]);

    $this->actingAs($superAdmin)
        ->get(route('provider.providers.fuels.index', $provider))
        ->assertOk()
        ->assertSee('Diesel');

    $this->get(route('provider.providers.fuels.create', $provider))
        ->assertOk()
        ->assertSee('Add branch fuel');

    $this->get(route('provider.providers.fuels.edit', [$provider, $branchFuel]))
        ->assertOk()
        ->assertSee('Edit Diesel');

    $this->get(route('provider.providers.drivers.edit', [$provider, $driver]))
        ->assertOk()
        ->assertSee($driverUser->name);
});

it('updates a driver only through their provider workspace', function () {
    $providerAdmin = User::factory()->create()->assignRole('provider');
    $provider = Provider::create(['name' => 'Driver Provider', 'status' => 'active']);
    $provider->users()->attach($providerAdmin->id, ['is_active' => true]);
    $branch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Driver Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);
    $driverUser = User::factory()->create()->assignRole('driver');
    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'provider_id' => $provider->id,
        'truck_plate' => 'UAA 101A',
        'available_status' => 'available',
    ]);

    $this->actingAs($providerAdmin)
        ->put(route('provider.providers.drivers.update', [$provider, $driver]), [
            'name' => 'Updated Driver',
            'phone' => '0700123456',
            'branch_id' => $branch->id,
            'truck_plate' => 'UAA 102A',
        ])
        ->assertRedirect(route('provider.providers.drivers.index', $provider, absolute: false));

    $this->assertDatabaseHas('users', ['id' => $driverUser->id, 'name' => 'Updated Driver', 'phone' => '0700123456']);
    $this->assertDatabaseHas('drivers', [
        'id' => $driver->id,
        'branch_id' => $branch->id,
        'truck_plate' => 'UAA 102A',
        'available_status' => 'available',
    ]);
});

it('creates customer accounts without granting a submitted privileged role', function () {
    $superAdmin = User::factory()->create()->assignRole('admin');

    $response = $this->actingAs($superAdmin)->post(route('admin.users.store'), [
        'name' => 'New Customer',
        'email' => 'new.customer@example.com',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
        'role' => 'admin',
    ]);

    $response->assertRedirect(route('admin.users.index', absolute: false));
    $createdUser = User::query()->where('email', 'new.customer@example.com')->firstOrFail();

    expect($createdUser->hasRole('customer'))->toBeTrue()
        ->and($createdUser->hasRole('admin'))->toBeFalse();
});

it('marks only the authenticated users notification as read', function () {
    $user = User::factory()->create()->assignRole('customer');
    $otherUser = User::factory()->create()->assignRole('customer');
    $notification = UserNotification::create([
        'user_id' => $otherUser->id,
        'title' => 'Private notification',
    ]);

    $this->actingAs($user)
        ->post(route('notifications.read', $notification))
        ->assertNotFound();

    $this->assertDatabaseHas('user_notifications', [
        'id' => $notification->id,
        'read_at' => null,
    ]);
});

it('marks an owned notification as read', function () {
    $user = User::factory()->create()->assignRole('customer');
    $notification = UserNotification::create([
        'user_id' => $user->id,
        'title' => 'Order updated',
    ]);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Order updated');

    $this->post(route('notifications.read', $notification))
        ->assertRedirect();

    $this->assertDatabaseMissing('user_notifications', [
        'id' => $notification->id,
        'read_at' => null,
    ]);
});
