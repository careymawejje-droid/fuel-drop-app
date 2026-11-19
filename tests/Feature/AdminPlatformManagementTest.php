<?php

use App\Mail\ContactMessage;
use App\Models\Branch;
use App\Models\BranchFuel;
use App\Models\Driver;
use App\Models\Fuel;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('creates and suspends the designated Provider account', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.providers.store'), [
            'name' => 'Managed Provider',
            'phone' => '0700111222',
            'email' => 'provider@example.com',
        ])
        ->assertRedirect();

    $provider = Provider::query()->where('name', 'Managed Provider')->firstOrFail();
    $this->post(route('admin.providers.accounts.store', $provider), [
        'name' => 'Provider User',
        'email' => 'provider.user@example.com',
        'phone' => '0700333444',
        'password' => 'provider-password',
        'password_confirmation' => 'provider-password',
    ])->assertRedirect();

    $provider->refresh();
    $providerUser = User::query()->where('email', 'provider.user@example.com')->firstOrFail();

    expect($providerUser->hasRole('provider'))->toBeTrue()
        ->and($provider->admin_user_id)->toBe($providerUser->id)
        ->and($providerUser->providers()->whereKey($provider->id)->wherePivot('is_active', true)->exists())->toBeTrue();

    $this->actingAs($providerUser)
        ->get(route('provider.dashboard'))
        ->assertOk();

    $this->actingAs($admin)
        ->put(route('admin.providers.accounts.update', $provider), ['is_active' => '0'])
        ->assertRedirect();

    $this->assertDatabaseHas('provider_user', [
        'provider_id' => $provider->id,
        'user_id' => $providerUser->id,
        'is_active' => false,
    ]);

    $this->actingAs($providerUser)
        ->get(route('provider.dashboard'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('admin.providers.accounts.update', $provider), ['is_active' => '1'])
        ->assertRedirect();

    $this->actingAs($providerUser)
        ->get(route('provider.dashboard'))
        ->assertOk();
});

it('persists platform settings and uses the configured support address', function () {
    Mail::fake();
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'contact_email' => 'help@managed.example',
            'delivery_fee' => '1250.50',
        ])
        ->assertRedirect(route('admin.settings.edit', absolute: false))
        ->assertSessionHas('status', 'Platform settings updated.');

    $this->assertDatabaseHas('platform_settings', [
        'setting_key' => 'platform',
        'contact_email' => 'help@managed.example',
        'delivery_fee' => 1250.5,
    ]);

    $this->post(route('contact.store'), [
        'name' => 'Customer',
        'email' => 'customer@example.com',
        'message' => 'Please call me about my order.',
    ])->assertRedirect(route('contact'));

    Mail::assertSent(ContactMessage::class, fn (ContactMessage $message): bool => $message->hasTo('help@managed.example'));

    $customer = User::factory()->create()->assignRole('customer');
    $providerAdmin = User::factory()->create()->assignRole('provider');
    $providerTeammate = User::factory()->create()->assignRole('provider');
    $inactiveProviderMember = User::factory()->create()->assignRole('provider');
    $provider = Provider::create([
        'admin_user_id' => $providerAdmin->id,
        'name' => 'Fee Provider',
        'status' => 'active',
    ]);
    $provider->users()->attach($providerAdmin->id, ['is_active' => true]);
    $provider->users()->attach($providerTeammate->id, ['is_active' => true]);
    $provider->users()->attach($inactiveProviderMember->id, ['is_active' => false]);
    $branch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Fee Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);
    $fuel = Fuel::create(['name' => 'Petrol', 'price_per_litre' => 5000, 'status' => 'available']);
    BranchFuel::create([
        'branch_id' => $branch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 5000,
        'available_stock_litres' => 10,
        'status' => 'active',
    ]);

    $this->actingAs($customer)
        ->post(route('customer.orders.store'), [
            'provider_id' => $provider->id,
            'fuel_id' => $fuel->id,
            'quantity' => 3,
            'address' => 'Kampala',
            'latitude' => '0.3476000',
            'longitude' => '32.5825000',
            'location_confirmed' => '1',
        ])
        ->assertRedirect();

    $order = Order::query()->where('user_id', $customer->id)->firstOrFail();
    expect((float) $order->total)->toBe(16250.5);
    $this->assertDatabaseHas('user_notifications', [
        'user_id' => $providerAdmin->id,
        'order_id' => $order->id,
        'title' => 'New order awaiting provider response',
    ]);
    $this->assertDatabaseHas('user_notifications', [
        'user_id' => $providerTeammate->id,
        'order_id' => $order->id,
        'title' => 'New order awaiting provider response',
    ]);
    $this->assertDatabaseMissing('user_notifications', [
        'user_id' => $inactiveProviderMember->id,
        'order_id' => $order->id,
        'title' => 'New order awaiting provider response',
    ]);
});

it('lets Admin review all platform users, orders, payments, and report totals', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $customer = User::factory()->create(['email' => 'visible.customer@example.com'])->assignRole('customer');
    $driverUser = User::factory()->create(['email' => 'visible.driver@example.com'])->assignRole('driver');
    $provider = Provider::create(['name' => 'Visible Provider', 'status' => 'active']);
    $branch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Visible Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);
    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'provider_id' => $provider->id,
        'branch_id' => $branch->id,
    ]);
    $order = Order::create([
        'order_number' => 'FD-ADMIN-OVERVIEW',
        'user_id' => $customer->id,
        'provider_id' => $provider->id,
        'branch_id' => $branch->id,
        'driver_id' => $driver->id,
        'total' => 10000,
        'delivery_address' => 'Kampala',
        'status' => 'pending',
        'placed_at' => now(),
    ]);
    Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'cash_on_delivery',
        'amount' => 10000,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee($customer->email)
        ->assertSee($driverUser->email);

    $this->get(route('admin.orders.index'))
        ->assertOk()
        ->assertSee('FD-ADMIN-OVERVIEW')
        ->assertSee('Visible Provider');

    $this->get(route('admin.payments.index'))
        ->assertOk()
        ->assertSee('FD-ADMIN-OVERVIEW')
        ->assertSee('Cash on delivery');

    $this->get(route('admin.reports.index'))
        ->assertOk()
        ->assertSee('Orders by status')
        ->assertSee('Payments by status')
        ->assertSee('Visible Provider')
        ->assertSee('Pending');
});
