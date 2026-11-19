<?php

use App\Models\Driver;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('lets Admins search users, change between the four roles, and preserves business relationships', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $user = User::factory()->create(['name' => 'Managed User'])->assignRole('customer');
    $provider = Provider::create(['name' => 'Managed Provider', 'status' => 'active']);
    $provider->users()->attach($user->id, ['is_active' => true]);
    $driver = Driver::create([
        'user_id' => $user->id,
        'provider_id' => $provider->id,
        'available_status' => 'assigned',
    ]);
    $order = Order::create([
        'user_id' => $user->id,
        'provider_id' => $provider->id,
        'driver_id' => $driver->id,
        'total' => 12000,
        'delivery_address' => 'Kampala',
        'status' => 'out_for_delivery',
    ]);
    $payment = Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'cash_on_delivery',
        'amount' => 12000,
        'status' => 'pending',
    ]);
    $user->givePermissionTo('manage providers');

    $this->actingAs($admin)->get(route('admin.users.index', ['q' => 'Managed User', 'role' => 'customer', 'status' => 'active']))
        ->assertOk()->assertSee('Managed User')->assertSee('Customer')->assertSee('Active');
    $this->get(route('admin.users.show', $user))->assertOk()->assertSee($provider->name)->assertSee('Orders');

    foreach (['driver', 'provider', 'customer', 'admin'] as $role) {
        $this->patch(route('admin.users.role', $user), ['role' => $role])
            ->assertRedirect(route('admin.users.show', $user, absolute: false));
        $user->refresh();
        expect($user->roles()->pluck('name')->all())->toBe([$role]);

        if ($role === 'driver') {
            expect($user->can('view assigned deliveries'))->toBeTrue()
                ->and($user->can('place orders'))->toBeFalse()
                ->and($user->can('manage providers'))->toBeFalse();
            $this->actingAs($user)->get(route('customer.orders.index'))->assertForbidden();
            $this->actingAs($admin);
        } elseif ($role === 'provider') {
            expect($user->can('view assigned deliveries'))->toBeFalse()
                ->and($user->can('manage provider branches'))->toBeTrue()
                ->and($user->can('manage providers'))->toBeFalse();
            $this->actingAs($user)->get(route('driver.deliveries.index'))->assertForbidden();
            $this->get(route('provider.dashboard'))->assertOk();
            $this->actingAs($admin);
        } elseif ($role === 'customer') {
            expect($user->can('place orders'))->toBeTrue()
                ->and($user->can('manage provider branches'))->toBeFalse();
            $this->actingAs($user)->get(route('provider.dashboard'))->assertForbidden();
            $this->actingAs($admin);
        }
    }

    expect($user->can('place orders'))->toBeTrue()
        ->and($user->can('view assigned deliveries'))->toBeTrue()
        ->and($user->can('manage providers'))->toBeTrue()
        ->and($user->providers()->whereKey($provider->id)->wherePivot('is_active', true)->exists())->toBeTrue()
        ->and($user->driver()->whereKey($driver->id)->exists())->toBeTrue()
        ->and(Order::query()->findOrFail($order->id)->driver_id)->toBe($driver->id)
        ->and(Payment::query()->findOrFail($payment->id)->order_id)->toBe($order->id);

    expect(Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')->all())
        ->toBe(['admin', 'customer', 'driver', 'provider']);
});

it('deactivates accounts without deleting records, blocks sessions and login, and allows reactivation', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $user = User::factory()->create()->assignRole('customer');
    $order = Order::create([
        'user_id' => $user->id,
        'total' => 9000,
        'delivery_address' => 'Kampala',
        'status' => 'pending',
    ]);
    $payment = Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'cash_on_delivery',
        'amount' => 9000,
        'status' => 'pending',
    ]);
    $tokenId = $user->createToken('existing-client')->accessToken->id;

    $this->actingAs($admin)
        ->patch(route('admin.users.status', $user), ['status' => 'inactive'])
        ->assertRedirect(route('admin.users.show', $user, absolute: false));

    expect($user->fresh()->status)->toBe('inactive');
    $this->assertDatabaseHas('users', ['id' => $user->id]);
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $user->id]);
    $this->assertDatabaseHas('payments', ['id' => $payment->id, 'order_id' => $order->id]);
    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);

    $this->actingAs($user)->get(route('customer.orders.index'))
        ->assertRedirect(route('login'));
    $this->assertGuest();
    $this->withSession(['login.id' => $user->id, 'login.remember' => false])
        ->get('/two-factor-challenge')
        ->assertRedirect(route('login'));
    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $this->assertGuest();

    $this->actingAs($admin)
        ->patch(route('admin.users.status', $user), ['status' => 'active'])
        ->assertRedirect(route('admin.users.show', $user, absolute: false));
    $this->post('/logout');
    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $this->assertAuthenticatedAs($user);
});

it('restricts user management to Admins and prevents invalid roles and self or last Admin lockout', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $customer = User::factory()->create()->assignRole('customer');

    foreach (['customer', 'driver', 'provider'] as $role) {
        $nonAdmin = User::factory()->create()->assignRole($role);
        $this->actingAs($nonAdmin)->get(route('admin.users.show', $admin))->assertForbidden();
        $this->patch(route('admin.users.role', $customer), ['role' => 'admin'])->assertForbidden();
        $this->patch(route('admin.users.status', $customer), ['status' => 'inactive'])->assertForbidden();
    }

    $this->actingAs($admin)
        ->patch(route('admin.users.role', $customer), ['role' => 'super-admin'])
        ->assertSessionHasErrors('role');
    $this->patch(route('admin.users.role', $customer), ['role' => 'provider'])->assertSessionHasErrors('role');
    $this->patch(route('admin.users.role', $customer), ['role' => 'driver'])->assertSessionHasErrors('role');
    $this->patch(route('admin.users.role', $admin), ['role' => 'customer'])->assertSessionHasErrors('role');
    $this->patch(route('admin.users.status', $admin), ['status' => 'inactive'])->assertSessionHasErrors('status');
    $this->patch(route('admin.users.status', $customer), ['status' => 'inactive']);
    $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active']);
    expect($admin->fresh()->hasRole('admin'))->toBeTrue()
        ->and(User::query()->where('status', 'active')->whereHas('roles', fn ($query) => $query->where('name', 'admin'))->count())->toBe(1);
});
