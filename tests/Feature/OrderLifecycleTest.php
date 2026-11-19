<?php

use App\Models\Branch;
use App\Models\BranchFuel;
use App\Models\Driver;
use App\Models\DriverLocation;
use App\Models\Fuel;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\StockReservation;
use App\Models\Truck;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('reserves inventory, assigns delivery, collects cash, and records customer confirmation', function () {
    $fixture = makeOrderLifecycleFixture(10, 2);
    $providerTeammate = User::factory()->create()->assignRole('provider');
    $fixture['provider']->users()->attach($providerTeammate->id, ['is_active' => true]);

    $this->actingAs($fixture['providerUser'])
        ->post(route('provider.providers.orders.accept', [$fixture['provider'], $fixture['order']]))
        ->assertRedirect(route('provider.providers.orders.show', [$fixture['provider'], $fixture['order']], absolute: false));

    $this->assertDatabaseHas('orders', ['id' => $fixture['order']->id, 'status' => 'accepted']);
    $this->assertDatabaseHas('stock_reservations', [
        'order_id' => $fixture['order']->id,
        'branch_fuel_id' => $fixture['branchFuel']->id,
        'quantity_litres' => 5,
        'status' => 'reserved',
    ]);
    $this->assertDatabaseHas('branch_fuel', ['id' => $fixture['branchFuel']->id, 'reserved_stock_litres' => 7]);

    $this->post(route('provider.providers.orders.assign', [$fixture['provider'], $fixture['order']]), [
        'driver_id' => $fixture['driver']->id,
        'truck_id' => $fixture['truck']->id,
    ])->assertRedirect(route('provider.providers.orders.show', [$fixture['provider'], $fixture['order']], absolute: false));

    $this->assertDatabaseHas('orders', [
        'id' => $fixture['order']->id,
        'driver_id' => $fixture['driver']->id,
        'truck_id' => $fixture['truck']->id,
        'status' => 'assigned',
    ]);
    $this->assertDatabaseHas('drivers', ['id' => $fixture['driver']->id, 'available_status' => 'assigned']);
    $this->assertDatabaseHas('trucks', ['id' => $fixture['truck']->id, 'status' => 'assigned']);
    $this->actingAs($fixture['driverUser'])
        ->get(route('driver.deliveries.index', ['q' => $fixture['order']->order_number]))
        ->assertOk()
        ->assertSee($fixture['order']->order_number);
    $this->assertDatabaseHas('user_notifications', [
        'user_id' => $providerTeammate->id,
        'order_id' => $fixture['order']->id,
        'title' => 'Delivery assigned for order FD-LIFECYCLE',
    ]);

    $this->actingAs($fixture['driverUser'])
        ->post(route('driver.deliveries.status', $fixture['order']), ['status' => 'fuel_loading'])
        ->assertRedirect(route('driver.deliveries.show', $fixture['order'], absolute: false));

    $this->assertDatabaseHas('orders', ['id' => $fixture['order']->id, 'status' => 'fuel_loading']);
    $this->assertDatabaseHas('branch_fuel', [
        'id' => $fixture['branchFuel']->id,
        'available_stock_litres' => 5,
        'reserved_stock_litres' => 2,
    ]);
    $this->assertDatabaseHas('stock_reservations', [
        'order_id' => $fixture['order']->id,
        'status' => 'consumed',
    ]);
    $this->assertDatabaseHas('user_notifications', [
        'user_id' => $providerTeammate->id,
        'order_id' => $fixture['order']->id,
        'title' => 'Order FD-LIFECYCLE status updated: fuel loading',
    ]);

    $this->post(route('driver.deliveries.status', $fixture['order']), ['status' => 'fuel_loading'])
        ->assertSessionHasErrors('status');
    $this->assertDatabaseHas('branch_fuel', [
        'id' => $fixture['branchFuel']->id,
        'available_stock_litres' => 5,
        'reserved_stock_litres' => 2,
    ]);

    $this->post(route('driver.deliveries.status', $fixture['order']), ['status' => 'out_for_delivery'])
        ->assertRedirect(route('driver.deliveries.show', $fixture['order'], absolute: false));

    $this->actingAs($fixture['customer'])
        ->post(route('customer.orders.confirm-delivery', $fixture['order']))
        ->assertForbidden();

    $this->actingAs($fixture['driverUser'])
        ->post(route('driver.deliveries.status', $fixture['order']), ['status' => 'delivered'])
        ->assertSessionHasErrors('status');
    $this->assertDatabaseHas('orders', ['id' => $fixture['order']->id, 'status' => 'out_for_delivery']);

    $this->actingAs($fixture['driverUser'])
        ->post(route('driver.deliveries.cash-collection', $fixture['order']), ['amount' => '25000.00'])
        ->assertRedirect(route('driver.deliveries.show', $fixture['order'], absolute: false));

    $this->get(route('driver.deliveries.show', $fixture['order']))
        ->assertOk()
        ->assertSee('Collected: UGX 25,000.00')
        ->assertSee('Collected at');

    $this->assertDatabaseHas('payments', [
        'order_id' => $fixture['order']->id,
        'amount' => 25000,
        'status' => 'paid',
        'collected_by_driver_id' => $fixture['driver']->id,
    ]);
    expect(Payment::query()->where('order_id', $fixture['order']->id)->firstOrFail()->collected_at)->not->toBeNull();

    $this->post(route('driver.deliveries.cash-collection', $fixture['order']), ['amount' => '25000.00'])
        ->assertForbidden();

    $this->actingAs($fixture['customer'])
        ->post(route('customer.orders.confirm-delivery', $fixture['order']))
        ->assertRedirect(route('customer.orders.show', $fixture['order'], absolute: false))
        ->assertSessionHas('status', 'Delivery confirmed.');

    $this->assertDatabaseHas('orders', ['id' => $fixture['order']->id, 'status' => 'delivered']);
    $this->assertDatabaseHas('branch_fuel', [
        'id' => $fixture['branchFuel']->id,
        'available_stock_litres' => 5,
        'reserved_stock_litres' => 2,
    ]);
    $this->assertDatabaseHas('stock_reservations', [
        'order_id' => $fixture['order']->id,
        'status' => 'consumed',
    ]);
    $this->assertDatabaseHas('drivers', ['id' => $fixture['driver']->id, 'available_status' => 'available']);
    $this->assertDatabaseHas('trucks', ['id' => $fixture['truck']->id, 'status' => 'available']);

});

it('rolls back fuel loading when the reservation cannot be reconciled', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $fixture['order']->update([
        'driver_id' => $fixture['driver']->id,
        'truck_id' => $fixture['truck']->id,
        'status' => 'assigned',
    ]);
    $fixture['driver']->update(['available_status' => 'assigned']);
    $fixture['truck']->update(['status' => 'assigned']);
    $fixture['branchFuel']->update(['reserved_stock_litres' => 4]);
    StockReservation::create([
        'order_id' => $fixture['order']->id,
        'branch_fuel_id' => $fixture['branchFuel']->id,
        'quantity_litres' => 5,
        'status' => 'reserved',
        'reserved_at' => now(),
    ]);

    $this->actingAs($fixture['driverUser'])
        ->post(route('driver.deliveries.status', $fixture['order']), ['status' => 'fuel_loading'])
        ->assertSessionHasErrors('status');

    $this->assertDatabaseHas('orders', ['id' => $fixture['order']->id, 'status' => 'assigned']);
    $this->assertDatabaseHas('branch_fuel', [
        'id' => $fixture['branchFuel']->id,
        'available_stock_litres' => 10,
        'reserved_stock_litres' => 4,
    ]);
    $this->assertDatabaseHas('stock_reservations', [
        'order_id' => $fixture['order']->id,
        'status' => 'reserved',
    ]);
});

it('rejects acceptance when unreserved stock cannot cover the order', function () {
    $fixture = makeOrderLifecycleFixture(6, 2);

    $this->actingAs($fixture['providerUser'])
        ->from(route('provider.providers.orders.show', [$fixture['provider'], $fixture['order']]))
        ->post(route('provider.providers.orders.accept', [$fixture['provider'], $fixture['order']]))
        ->assertRedirect(route('provider.providers.orders.show', [$fixture['provider'], $fixture['order']], absolute: false))
        ->assertSessionHasErrors([
            'order' => 'There is not enough unreserved stock to accept this order.',
        ]);

    $this->assertDatabaseHas('orders', ['id' => $fixture['order']->id, 'status' => 'pending']);
    $this->assertDatabaseHas('branch_fuel', ['id' => $fixture['branchFuel']->id, 'reserved_stock_litres' => 2]);
    $this->assertDatabaseMissing('stock_reservations', ['order_id' => $fixture['order']->id]);
});

it('rejects a pending order without reserving inventory', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);

    $this->actingAs($fixture['providerUser'])
        ->post(route('provider.providers.orders.reject', [$fixture['provider'], $fixture['order']]), [
            'rejection_reason' => 'The requested quantity is unavailable.',
        ])
        ->assertRedirect(route('provider.providers.orders.index', $fixture['provider'], absolute: false));

    $this->assertDatabaseHas('orders', [
        'id' => $fixture['order']->id,
        'status' => 'rejected',
        'rejection_reason' => 'The requested quantity is unavailable.',
    ]);
    $this->assertDatabaseHas('user_notifications', [
        'user_id' => $fixture['customer']->id,
        'order_id' => $fixture['order']->id,
        'title' => 'Your provider rejected the order',
    ]);
    $this->assertDatabaseHas('branch_fuel', ['id' => $fixture['branchFuel']->id, 'reserved_stock_litres' => 0]);
    $this->assertDatabaseMissing('stock_reservations', ['order_id' => $fixture['order']->id]);

    $this->actingAs($fixture['customer'])
        ->get(route('customer.orders.show', $fixture['order']))
        ->assertOk()
        ->assertSee('The requested quantity is unavailable.');
});

it('requires a rejection reason before a provider can reject an order', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);

    $this->actingAs($fixture['providerUser'])
        ->from(route('provider.providers.orders.show', [$fixture['provider'], $fixture['order']]))
        ->post(route('provider.providers.orders.reject', [$fixture['provider'], $fixture['order']]), [])
        ->assertRedirect(route('provider.providers.orders.show', [$fixture['provider'], $fixture['order']], absolute: false))
        ->assertSessionHasErrors('rejection_reason');

    $this->assertDatabaseHas('orders', [
        'id' => $fixture['order']->id,
        'status' => 'pending',
        'rejection_reason' => null,
    ]);
});

it('shows reserved stock and assigned driver locations to the owning provider', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);

    $this->actingAs($fixture['providerUser'])
        ->post(route('provider.providers.orders.accept', [$fixture['provider'], $fixture['order']]))
        ->assertRedirect();
    $this->post(route('provider.providers.orders.assign', [$fixture['provider'], $fixture['order']]), [
        'driver_id' => $fixture['driver']->id,
        'truck_id' => $fixture['truck']->id,
    ])->assertRedirect();

    DriverLocation::create([
        'driver_id' => $fixture['driver']->id,
        'order_id' => $fixture['order']->id,
        'latitude' => '0.3476',
        'longitude' => '32.5825',
        'recorded_at' => now(),
    ]);

    $this->get(route('provider.providers.orders.show', [$fixture['provider'], $fixture['order']]))
        ->assertOk()
        ->assertSee('Stock reservations')
        ->assertSee('Petrol')
        ->assertSee('Reserved')
        ->assertSee('0.3476')
        ->assertSee('32.5825');
});

it('accepts active assigned GPS while preserving general driver location updates', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $fixture['order']->update(['driver_id' => $fixture['driver']->id, 'status' => 'out_for_delivery']);

    $this->actingAs($fixture['driverUser'])->postJson(route('driver.location.update'), [
        'order_id' => $fixture['order']->id, 'latitude' => 0.3476, 'longitude' => 32.5825, 'accuracy_metres' => 12.5,
    ])->assertCreated()->assertJsonPath('location.order_id', $fixture['order']->id);

    $this->postJson(route('driver.location.update'), [
        'latitude' => 0.35, 'longitude' => 32.6,
    ])->assertCreated()->assertJsonPath('location.order_id', null);

    $fixture['order']->update(['status' => 'delivered']);
    $this->postJson(route('driver.location.update'), [
        'order_id' => $fixture['order']->id, 'latitude' => 0.3476, 'longitude' => 32.5825,
    ])->assertForbidden();
});

it('shows the saved customer destination to the assigned driver', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $fixture['order']->update([
        'driver_id' => $fixture['driver']->id,
        'status' => 'assigned',
        'delivery_address' => 'Uganda, District: Wakiso, Area: Kiwatule, Landmark/Directions: Near market',
        'delivery_latitude' => 0.42,
        'delivery_longitude' => 32.61,
    ]);

    $this->actingAs($fixture['driverUser'])
        ->get(route('driver.deliveries.show', $fixture['order']))
        ->assertOk()
        ->assertSee('Delivery point')
        ->assertSee('District: Wakiso')
        ->assertSee('Customer destination');
});

it('returns only the owning customer latest location and blocks other customers', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $fixture['order']->update(['driver_id' => $fixture['driver']->id, 'status' => 'out_for_delivery', 'delivery_latitude' => 0.31, 'delivery_longitude' => 32.55]);
    DriverLocation::create(['driver_id' => $fixture['driver']->id, 'order_id' => $fixture['order']->id, 'latitude' => 0.34, 'longitude' => 32.58, 'recorded_at' => now()]);

    $this->actingAs($fixture['customer'])->getJson(route('customer.orders.tracking', $fixture['order']))
        ->assertOk()->assertJsonPath('location.latitude', 0.34)->assertJsonPath('destination.latitude', 0.31)
        ->assertJsonPath('driver_assigned', true)->assertJsonMissingPath('order_id')->assertJsonMissingPath('driver');
    $other = User::factory()->create()->assignRole('customer');
    $this->actingAs($other)->getJson(route('customer.orders.tracking', $fixture['order']))->assertForbidden();
});

it('limits provider and admin tracking to permitted deliveries and marks stale locations', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $fixture['order']->update(['driver_id' => $fixture['driver']->id, 'status' => 'out_for_delivery']);
    DriverLocation::create([
        'driver_id' => $fixture['driver']->id,
        'order_id' => $fixture['order']->id,
        'latitude' => 0.34,
        'longitude' => 32.58,
        'recorded_at' => now()->subMinutes(10),
    ]);

    $this->actingAs($fixture['providerUser'])
        ->getJson(route('provider.providers.tracking.data', [$fixture['provider'], $fixture['order']]))
        ->assertOk()->assertJsonPath('fresh', false)->assertJsonPath('age_seconds', fn ($age) => $age >= 600);

    $other = makeOrderLifecycleFixture(10, 0, 'OTHER');
    $this->getJson(route('provider.providers.tracking.data', [$fixture['provider'], $other['order']]))->assertNotFound();

    $admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($admin)->getJson(route('admin.tracking.data', $fixture['order']))->assertOk();
    $this->get(route('admin.tracking.index'))->assertOk()->assertSee($fixture['order']->order_number);
});

it('does not disclose terminal delivery coordinates through tracking reads', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $fixture['order']->update(['driver_id' => $fixture['driver']->id, 'status' => 'delivered']);
    DriverLocation::create([
        'driver_id' => $fixture['driver']->id,
        'order_id' => $fixture['order']->id,
        'latitude' => 0.34,
        'longitude' => 32.58,
        'recorded_at' => now(),
    ]);

    $this->actingAs($fixture['customer'])->getJson(route('customer.orders.tracking', $fixture['order']))
        ->assertOk()->assertJsonPath('location', null)->assertJsonPath('status', 'delivered');
});

it('allows customers to cancel their own orders before fuel loading', function (string $initialStatus) {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $otherCustomer = User::factory()->create()->assignRole('customer');

    if ($initialStatus !== 'pending') {
        $this->actingAs($fixture['providerUser'])
            ->post(route('provider.providers.orders.accept', [$fixture['provider'], $fixture['order']]))
            ->assertRedirect();
    }

    if ($initialStatus === 'assigned') {
        $this->post(route('provider.providers.orders.assign', [$fixture['provider'], $fixture['order']]), [
            'driver_id' => $fixture['driver']->id,
            'truck_id' => $fixture['truck']->id,
        ])->assertRedirect();
    }

    $this->actingAs($otherCustomer)
        ->post(route('customer.orders.cancel', $fixture['order']))
        ->assertForbidden();

    $this->actingAs($fixture['customer'])
        ->post(route('customer.orders.cancel', $fixture['order']))
        ->assertRedirect(route('customer.orders.show', $fixture['order'], absolute: false))
        ->assertSessionHas('status', 'Order cancelled.');

    $this->assertDatabaseHas('orders', [
        'id' => $fixture['order']->id,
        'status' => 'cancelled',
        'driver_id' => null,
        'truck_id' => null,
    ]);
    $this->assertDatabaseHas('branch_fuel', [
        'id' => $fixture['branchFuel']->id,
        'available_stock_litres' => 10,
        'reserved_stock_litres' => 0,
    ]);
    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $fixture['order']->id,
        'status' => 'cancelled',
        'changed_by' => $fixture['customer']->id,
    ]);
    $this->assertDatabaseHas('user_notifications', [
        'user_id' => $fixture['customer']->id,
        'order_id' => $fixture['order']->id,
        'title' => 'Your order was cancelled',
    ]);
    $this->assertDatabaseHas('user_notifications', [
        'user_id' => $fixture['providerUser']->id,
        'order_id' => $fixture['order']->id,
        'title' => 'Customer cancelled order FD-LIFECYCLE',
    ]);

    if ($initialStatus === 'pending') {
        $this->assertDatabaseMissing('stock_reservations', ['order_id' => $fixture['order']->id]);
    } else {
        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $fixture['order']->id,
            'status' => 'released',
        ]);
        expect(StockReservation::query()->where('order_id', $fixture['order']->id)->firstOrFail()->released_at)->not->toBeNull();
    }

    if ($initialStatus === 'assigned') {
        $this->assertDatabaseHas('drivers', ['id' => $fixture['driver']->id, 'available_status' => 'available']);
        $this->assertDatabaseHas('trucks', ['id' => $fixture['truck']->id, 'status' => 'available']);
    }

    $this->post(route('customer.orders.cancel', $fixture['order']))
        ->assertForbidden();

    $this->assertDatabaseHas('branch_fuel', [
        'id' => $fixture['branchFuel']->id,
        'reserved_stock_litres' => 0,
    ]);
})->with([
    'awaiting provider response' => 'pending',
    'accepted and awaiting driver assignment' => 'accepted',
    'driver assigned before loading' => 'assigned',
]);

it('forbids customer cancellation once fuel loading has started', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);

    $this->actingAs($fixture['providerUser'])
        ->post(route('provider.providers.orders.accept', [$fixture['provider'], $fixture['order']]))
        ->assertRedirect();
    $this->post(route('provider.providers.orders.assign', [$fixture['provider'], $fixture['order']]), [
        'driver_id' => $fixture['driver']->id,
        'truck_id' => $fixture['truck']->id,
    ])->assertRedirect();

    $this->actingAs($fixture['driverUser'])
        ->post(route('driver.deliveries.status', $fixture['order']), ['status' => 'fuel_loading'])
        ->assertRedirect();

    $this->actingAs($fixture['customer'])
        ->post(route('customer.orders.cancel', $fixture['order']))
        ->assertForbidden();

    $this->assertDatabaseHas('orders', [
        'id' => $fixture['order']->id,
        'status' => 'fuel_loading',
        'driver_id' => $fixture['driver']->id,
        'truck_id' => $fixture['truck']->id,
    ]);
    $this->assertDatabaseHas('branch_fuel', [
        'id' => $fixture['branchFuel']->id,
        'available_stock_litres' => 5,
        'reserved_stock_litres' => 0,
    ]);
    $this->assertDatabaseHas('stock_reservations', [
        'order_id' => $fixture['order']->id,
        'status' => 'consumed',
    ]);
    $this->assertDatabaseMissing('order_status_histories', [
        'order_id' => $fixture['order']->id,
        'status' => 'cancelled',
    ]);
});

it('rejects incorrect COD amounts and payment records belonging to another driver', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $fixture['order']->update([
        'driver_id' => $fixture['driver']->id,
        'status' => 'out_for_delivery',
    ]);

    $this->actingAs($fixture['driverUser'])
        ->post(route('driver.deliveries.cash-collection', $fixture['order']), ['amount' => '24999.99'])
        ->assertSessionHasErrors('amount');

    $this->assertDatabaseHas('payments', [
        'order_id' => $fixture['order']->id,
        'status' => 'pending',
        'amount' => 25000,
        'collected_at' => null,
        'collected_by_driver_id' => null,
    ]);

    $otherDriverUser = User::factory()->create()->assignRole('driver');
    Driver::create([
        'user_id' => $otherDriverUser->id,
        'provider_id' => $fixture['provider']->id,
        'available_status' => 'available',
    ]);

    $this->actingAs($otherDriverUser)
        ->post(route('driver.deliveries.cash-collection', $fixture['order']), ['amount' => '25000.00'])
        ->assertForbidden();

    $this->assertDatabaseHas('payments', [
        'order_id' => $fixture['order']->id,
        'status' => 'pending',
        'collected_by_driver_id' => null,
    ]);
});

it('refuses COD collection when an order has duplicate payment records', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $fixture['order']->update([
        'driver_id' => $fixture['driver']->id,
        'status' => 'out_for_delivery',
    ]);
    $fixture['order']->payments()->create([
        'payment_method' => 'cash_on_delivery',
        'amount' => 25000,
        'status' => 'pending',
    ]);

    $this->actingAs($fixture['driverUser'])
        ->post(route('driver.deliveries.cash-collection', $fixture['order']), ['amount' => '25000.00'])
        ->assertForbidden();

    $this->assertDatabaseCount('payments', 2);
    $this->assertDatabaseHas('orders', ['id' => $fixture['order']->id, 'status' => 'out_for_delivery']);
});

it('formats provider order dates and limits payment details to the owning provider', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $fixture['order']->update([
        'driver_id' => $fixture['driver']->id,
        'status' => 'out_for_delivery',
        'placed_at' => '2026-11-17 10:30:00',
    ]);
    $fixture['driver']->update(['available_status' => 'assigned']);
    $fixture['order']->payment()->update([
        'status' => 'paid',
        'collected_at' => now(),
        'collected_by_driver_id' => $fixture['driver']->id,
    ]);

    $this->actingAs($fixture['providerUser'])
        ->get(route('provider.providers.orders.index', $fixture['provider']))
        ->assertOk()
        ->assertSee('17 Nov 2026 10:30')
        ->assertSee('Awaiting Customer Confirmation')
        ->assertSee('UGX 25,000.00');

    $this->get(route('provider.providers.orders.show', [$fixture['provider'], $fixture['order']]))
        ->assertOk()
        ->assertSee('Collected UGX 25,000.00')
        ->assertSee('Collected at');

    $this->actingAs($fixture['customer'])
        ->get(route('customer.orders.show', $fixture['order']))
        ->assertOk()
        ->assertSee('UGX 25,000.00')
        ->assertSee('Collected at');

    $otherFixture = makeOrderLifecycleFixture(10, 0, 'OTHER');
    $otherFixture['provider']->update(['name' => 'Other Lifecycle Provider']);
    $otherFixture['order']->update(['order_number' => 'FD-OTHER-PROVIDER']);
    $this->actingAs($fixture['providerUser'])
        ->get(route('provider.providers.orders.show', [$otherFixture['provider'], $otherFixture['order']]))
        ->assertForbidden();

    $admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($admin)
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertSee('FD-LIFECYCLE')
        ->assertSee('FD-OTHER-PROVIDER')
        ->assertSee($fixture['driverUser']->name);
});

it('does not allow a provider to assign resources owned by another provider', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $fixture['order']->update(['status' => 'accepted']);

    $otherProvider = Provider::create(['name' => 'Other Provider', 'status' => 'active']);
    $otherDriverUser = User::factory()->create()->assignRole('driver');
    $otherDriver = Driver::create([
        'user_id' => $otherDriverUser->id,
        'provider_id' => $otherProvider->id,
        'available_status' => 'available',
    ]);
    $otherTruck = Truck::create([
        'provider_id' => $otherProvider->id,
        'name' => 'Other Truck',
        'plate' => 'UAX 002B',
        'status' => 'available',
    ]);

    $this->actingAs($fixture['providerUser'])
        ->post(route('provider.providers.orders.assign', [$fixture['provider'], $fixture['order']]), [
            'driver_id' => $otherDriver->id,
            'truck_id' => $otherTruck->id,
        ])
        ->assertSessionHasErrors(['driver_id', 'truck_id']);

    $this->assertDatabaseHas('orders', ['id' => $fixture['order']->id, 'status' => 'accepted', 'driver_id' => null]);
    $this->assertDatabaseHas('trucks', ['id' => $otherTruck->id, 'status' => 'available']);
});

it('releases old resources when a provider reassigns a delivery before fuel loading', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $replacementDriverUser = User::factory()->create()->assignRole('driver');
    $replacementDriver = Driver::create([
        'user_id' => $replacementDriverUser->id,
        'provider_id' => $fixture['provider']->id,
        'branch_id' => $fixture['order']->branch_id,
        'available_status' => 'available',
    ]);
    $replacementTruck = Truck::create([
        'provider_id' => $fixture['provider']->id,
        'branch_id' => $fixture['order']->branch_id,
        'name' => 'Replacement Truck',
        'plate' => 'UAX 003B',
        'status' => 'available',
    ]);

    $this->actingAs($fixture['providerUser'])
        ->post(route('provider.providers.orders.accept', [$fixture['provider'], $fixture['order']]))
        ->assertRedirect();
    $this->post(route('provider.providers.orders.assign', [$fixture['provider'], $fixture['order']]), [
        'driver_id' => $fixture['driver']->id,
        'truck_id' => $fixture['truck']->id,
    ])->assertRedirect();
    $this->post(route('provider.providers.orders.assign', [$fixture['provider'], $fixture['order']]), [
        'driver_id' => $replacementDriver->id,
        'truck_id' => $replacementTruck->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('orders', [
        'id' => $fixture['order']->id,
        'driver_id' => $replacementDriver->id,
        'truck_id' => $replacementTruck->id,
        'status' => 'assigned',
    ]);
    $this->assertDatabaseHas('drivers', ['id' => $fixture['driver']->id, 'available_status' => 'available']);
    $this->assertDatabaseHas('trucks', ['id' => $fixture['truck']->id, 'status' => 'available']);
    $this->assertDatabaseHas('drivers', ['id' => $replacementDriver->id, 'available_status' => 'assigned']);
    $this->assertDatabaseHas('trucks', ['id' => $replacementTruck->id, 'status' => 'assigned']);

    $this->actingAs($replacementDriverUser)
        ->post(route('driver.deliveries.status', $fixture['order']), ['status' => 'fuel_loading'])
        ->assertRedirect();
    $this->actingAs($fixture['providerUser'])
        ->from(route('provider.providers.orders.show', [$fixture['provider'], $fixture['order']]))
        ->post(route('provider.providers.orders.assign', [$fixture['provider'], $fixture['order']]), [
            'driver_id' => $fixture['driver']->id,
            'truck_id' => $fixture['truck']->id,
        ])
        ->assertRedirect(route('provider.providers.orders.show', [$fixture['provider'], $fixture['order']], absolute: false))
        ->assertSessionHasErrors('order');

    $this->assertDatabaseHas('orders', [
        'id' => $fixture['order']->id,
        'driver_id' => $replacementDriver->id,
        'truck_id' => $replacementTruck->id,
        'status' => 'fuel_loading',
    ]);
});

it('rejects assignments when resources do not match the order branch or capacity', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $fixture['order']->update(['status' => 'accepted']);
    $otherBranch = Branch::create([
        'provider_id' => $fixture['provider']->id,
        'name' => 'Other Lifecycle Branch',
        'address' => 'Entebbe',
        'latitude' => 0.0512,
        'longitude' => 32.4637,
    ]);
    $fixture['truck']->update(['branch_id' => $otherBranch->id]);

    $this->actingAs($fixture['providerUser'])
        ->post(route('provider.providers.orders.assign', [$fixture['provider'], $fixture['order']]), [
            'driver_id' => $fixture['driver']->id,
            'truck_id' => $fixture['truck']->id,
        ])
        ->assertSessionHasErrors('driver_id');

    $fixture['truck']->update([
        'branch_id' => $fixture['order']->branch_id,
        'capacity_litres' => 4,
    ]);

    $this->post(route('provider.providers.orders.assign', [$fixture['provider'], $fixture['order']]), [
        'driver_id' => $fixture['driver']->id,
        'truck_id' => $fixture['truck']->id,
    ])
        ->assertSessionHasErrors('truck_id');

    $this->assertDatabaseHas('orders', [
        'id' => $fixture['order']->id,
        'status' => 'accepted',
        'driver_id' => null,
        'truck_id' => null,
    ]);
    $this->assertDatabaseHas('trucks', ['id' => $fixture['truck']->id, 'status' => 'available']);
});

it('does not assign a resource that already has another active delivery', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $fixture['order']->update(['status' => 'accepted']);
    Order::create([
        'order_number' => 'FD-RESOURCE-CONFLICT',
        'user_id' => $fixture['customer']->id,
        'provider_id' => $fixture['provider']->id,
        'branch_id' => $fixture['order']->branch_id,
        'driver_id' => $fixture['driver']->id,
        'total' => 10000,
        'delivery_address' => 'Kampala',
        'status' => 'out_for_delivery',
    ]);

    $this->actingAs($fixture['providerUser'])
        ->post(route('provider.providers.orders.assign', [$fixture['provider'], $fixture['order']]), [
            'driver_id' => $fixture['driver']->id,
            'truck_id' => $fixture['truck']->id,
        ])
        ->assertSessionHasErrors('driver_id');

    $this->assertDatabaseHas('orders', [
        'id' => $fixture['order']->id,
        'status' => 'accepted',
        'driver_id' => null,
        'truck_id' => null,
    ]);
    $this->assertDatabaseHas('trucks', ['id' => $fixture['truck']->id, 'status' => 'available']);
});

it('lets providers deactivate resources but prevents deactivation during an active delivery', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $providerTeammate = User::factory()->create()->assignRole('provider');
    $fixture['provider']->users()->attach($providerTeammate->id, ['is_active' => true]);
    $fixture['order']->update(['status' => 'accepted']);

    $this->actingAs($fixture['providerUser'])
        ->put(route('provider.providers.drivers.update', [$fixture['provider'], $fixture['driver']]), [
            'name' => $fixture['driverUser']->name,
            'phone' => '0700123456',
            'branch_id' => $fixture['order']->branch_id,
            'available_status' => 'inactive',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('drivers', ['id' => $fixture['driver']->id, 'available_status' => 'inactive']);
    $this->assertDatabaseHas('user_notifications', [
        'user_id' => $providerTeammate->id,
        'title' => 'Driver availability updated for '.$fixture['driverUser']->name,
    ]);

    $this->put(route('provider.trucks.update', [$fixture['provider'], $fixture['truck']]), [
        'branch_id' => $fixture['order']->branch_id,
        'name' => $fixture['truck']->name,
        'plate' => $fixture['truck']->plate,
        'status' => 'inactive',
    ])->assertRedirect();

    $this->post(route('provider.providers.orders.assign', [$fixture['provider'], $fixture['order']]), [
        'driver_id' => $fixture['driver']->id,
        'truck_id' => $fixture['truck']->id,
    ])->assertSessionHasErrors('driver_id');

    $this->put(route('provider.providers.drivers.update', [$fixture['provider'], $fixture['driver']]), [
        'name' => $fixture['driverUser']->name,
        'phone' => '0700123456',
        'branch_id' => $fixture['order']->branch_id,
        'available_status' => 'available',
    ])->assertRedirect();
    $this->put(route('provider.trucks.update', [$fixture['provider'], $fixture['truck']]), [
        'branch_id' => $fixture['order']->branch_id,
        'name' => $fixture['truck']->name,
        'plate' => $fixture['truck']->plate,
        'status' => 'available',
    ])->assertRedirect();

    $this->post(route('provider.providers.orders.assign', [$fixture['provider'], $fixture['order']]), [
        'driver_id' => $fixture['driver']->id,
        'truck_id' => $fixture['truck']->id,
    ])->assertRedirect();
    $this->put(route('provider.providers.drivers.update', [$fixture['provider'], $fixture['driver']]), [
        'name' => $fixture['driverUser']->name,
        'phone' => '0700123456',
        'branch_id' => $fixture['order']->branch_id,
        'available_status' => 'inactive',
    ])->assertSessionHasErrors('available_status');
    $this->put(route('provider.trucks.update', [$fixture['provider'], $fixture['truck']]), [
        'branch_id' => $fixture['order']->branch_id,
        'name' => $fixture['truck']->name,
        'plate' => $fixture['truck']->plate,
        'status' => 'inactive',
    ])->assertSessionHasErrors('status');

    $this->assertDatabaseHas('drivers', ['id' => $fixture['driver']->id, 'available_status' => 'assigned']);
    $this->assertDatabaseHas('trucks', ['id' => $fixture['truck']->id, 'status' => 'assigned']);
});

it('rejects invalid driver status transitions without consuming reserved stock', function () {
    $fixture = makeOrderLifecycleFixture(10, 0);
    $fixture['order']->update([
        'driver_id' => $fixture['driver']->id,
        'truck_id' => $fixture['truck']->id,
        'status' => 'assigned',
    ]);
    $fixture['driver']->update(['available_status' => 'assigned']);
    $fixture['truck']->update(['status' => 'assigned']);
    $fixture['branchFuel']->update(['reserved_stock_litres' => 5]);
    StockReservation::create([
        'order_id' => $fixture['order']->id,
        'branch_fuel_id' => $fixture['branchFuel']->id,
        'quantity_litres' => 5,
        'status' => 'reserved',
        'reserved_at' => now(),
    ]);

    $this->actingAs($fixture['driverUser'])
        ->from(route('driver.deliveries.show', $fixture['order']))
        ->post(route('driver.deliveries.status', $fixture['order']), ['status' => 'delivered'])
        ->assertRedirect(route('driver.deliveries.show', $fixture['order']))
        ->assertSessionHasErrors('status');

    $this->assertDatabaseHas('orders', ['id' => $fixture['order']->id, 'status' => 'assigned']);
    $this->assertDatabaseHas('branch_fuel', [
        'id' => $fixture['branchFuel']->id,
        'available_stock_litres' => 10,
        'reserved_stock_litres' => 5,
    ]);
    $this->assertDatabaseHas('stock_reservations', [
        'order_id' => $fixture['order']->id,
        'status' => 'reserved',
    ]);
});

/**
 * @return array{
 *     provider: Provider,
 *     providerUser: User,
 *     customer: User,
 *     driverUser: User,
 *     branchFuel: BranchFuel,
 *     order: Order,
 *     driver: Driver,
 *     truck: Truck
 * }
 */
function makeOrderLifecycleFixture(float $availableStock, float $reservedStock, string $suffix = ''): array
{
    $suffix = $suffix === '' ? '' : '-'.$suffix;
    $providerUser = User::factory()->create()->assignRole('provider');
    $provider = Provider::create([
        'admin_user_id' => $providerUser->id,
        'name' => 'Lifecycle Provider'.$suffix,
        'status' => 'active',
    ]);
    $provider->users()->attach($providerUser->id, ['is_active' => true]);
    $customer = User::factory()->create()->assignRole('customer');
    $driverUser = User::factory()->create()->assignRole('driver');
    $branch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Lifecycle Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
        'status' => 'active',
    ]);
    $fuel = Fuel::create(['name' => 'Petrol'.$suffix, 'price_per_litre' => 5000, 'status' => 'available']);
    $branchFuel = BranchFuel::create([
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 5000,
        'available_stock_litres' => $availableStock,
        'reserved_stock_litres' => $reservedStock,
        'status' => 'active',
    ]);
    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'provider_id' => $provider->id,
        'branch_id' => $branch->id,
        'available_status' => 'available',
    ]);
    $truck = Truck::create([
        'provider_id' => $provider->id,
        'branch_id' => $branch->id,
        'name' => 'Lifecycle Truck',
        'plate' => 'UAX 001B'.$suffix,
        'status' => 'available',
    ]);
    $order = Order::create([
        'order_number' => 'FD-LIFECYCLE'.$suffix,
        'user_id' => $customer->id,
        'provider_id' => $provider->id,
        'branch_id' => $branch->id,
        'total' => 25000,
        'delivery_address' => 'Kampala',
        'status' => 'pending',
        'placed_at' => now(),
    ]);
    $order->fuels()->attach($fuel->id, [
        'branch_fuel_id' => $branchFuel->id,
        'quantity_litres' => 5,
        'unit_price' => 5000,
    ]);
    Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'cash_on_delivery',
        'amount' => 25000,
        'status' => 'pending',
    ]);

    return compact('provider', 'providerUser', 'customer', 'driverUser', 'branchFuel', 'order', 'driver', 'truck');
}
