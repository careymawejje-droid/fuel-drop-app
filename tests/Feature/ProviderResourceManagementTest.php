<?php

use App\Models\Branch;
use App\Models\BranchFuel;
use App\Models\Driver;
use App\Models\Fuel;
use App\Models\Provider;
use App\Models\Truck;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('manages branches and trucks within the Provider workspace', function () {
    $providerUser = User::factory()->create()->assignRole('provider');
    $provider = Provider::create([
        'admin_user_id' => $providerUser->id,
        'name' => 'Resource Provider',
        'status' => 'active',
    ]);
    $provider->users()->attach($providerUser->id, ['is_active' => true]);
    $branch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Existing Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
        'status' => 'active',
    ]);
    $truck = Truck::create([
        'provider_id' => $provider->id,
        'branch_id' => $branch->id,
        'name' => 'Existing Truck',
        'plate' => 'UAX 100B',
        'status' => 'available',
    ]);

    $this->actingAs($providerUser)
        ->get(route('provider.branches.index', $provider))
        ->assertOk()
        ->assertSee('Existing Branch');

    $this->get(route('provider.branches.create', $provider))->assertOk();
    $this->get(route('provider.branches.edit', [$provider, $branch]))->assertOk();
    $this->get(route('provider.trucks.index', $provider))->assertOk()->assertSee('Existing Truck');
    $this->get(route('provider.trucks.create', $provider))->assertOk();
    $this->get(route('provider.trucks.edit', [$provider, $truck]))->assertOk();

    $this->post(route('provider.branches.store', $provider), [
        'name' => 'New Branch',
        'address' => 'Entebbe',
        'latitude' => '0.0512',
        'longitude' => '32.4637',
        'service_radius_metres' => 5000,
        'status' => 'active',
    ])->assertRedirect(route('provider.branches.index', $provider, absolute: false));

    $newBranch = Branch::query()->where('name', 'New Branch')->firstOrFail();
    $this->assertDatabaseHas('branches', ['id' => $newBranch->id, 'provider_id' => $provider->id]);

    $this->put(route('provider.branches.update', [$provider, $branch]), [
        'name' => 'Updated Branch',
        'address' => 'Kampala Central',
        'latitude' => '0.3476',
        'longitude' => '32.5825',
        'service_radius_metres' => 7000,
        'status' => 'active',
    ])->assertRedirect(route('provider.branches.index', $provider, absolute: false));

    $this->post(route('provider.trucks.store', $provider), [
        'branch_id' => $newBranch->id,
        'name' => 'New Truck',
        'plate' => 'UAX 101B',
        'capacity_litres' => '8000',
        'status' => 'available',
    ])->assertRedirect(route('provider.trucks.index', $provider, absolute: false));

    $newTruck = Truck::query()->where('plate', 'UAX 101B')->firstOrFail();
    $this->assertDatabaseHas('trucks', [
        'id' => $newTruck->id,
        'provider_id' => $provider->id,
        'branch_id' => $newBranch->id,
        'status' => 'available',
    ]);

    $otherProvider = Provider::create(['name' => 'Other Resource Provider', 'status' => 'active']);
    $otherBranch = Branch::create([
        'provider_id' => $otherProvider->id,
        'name' => 'Other Branch',
        'address' => 'Jinja',
        'latitude' => 0.4479,
        'longitude' => 33.2026,
    ]);
    $otherDriverUser = User::factory()->create()->assignRole('driver');
    $otherDriver = Driver::create([
        'user_id' => $otherDriverUser->id,
        'provider_id' => $otherProvider->id,
        'branch_id' => $otherBranch->id,
    ]);
    $otherTruck = Truck::create([
        'provider_id' => $otherProvider->id,
        'branch_id' => $otherBranch->id,
        'name' => 'Other Provider Truck',
        'plate' => 'UAX 102B',
        'status' => 'available',
    ]);

    $this->get(route('provider.branches.edit', [$provider, $otherBranch]))
        ->assertNotFound();
    $this->get(route('provider.providers.drivers.edit', [$provider, $otherDriver]))
        ->assertNotFound();
    $this->put(route('provider.providers.drivers.update', [$provider, $otherDriver]), [
        'name' => $otherDriverUser->name,
        'available_status' => 'inactive',
    ])->assertNotFound();
    $this->get(route('provider.trucks.edit', [$provider, $otherTruck]))
        ->assertNotFound();
    $this->put(route('provider.trucks.update', [$provider, $otherTruck]), [
        'name' => 'Changed by another provider',
        'plate' => 'UAX 103B',
        'status' => 'inactive',
    ])->assertNotFound();
    $this->assertDatabaseHas('trucks', [
        'id' => $otherTruck->id,
        'name' => 'Other Provider Truck',
        'status' => 'available',
    ]);
});

it('updates only its own inventory when a provider-submitted record id is changed', function () {
    $providerAdmin = User::factory()->create()->assignRole('provider');
    $provider = Provider::create(['name' => 'Inventory Owner', 'status' => 'active']);
    $provider->users()->attach($providerAdmin->id, ['is_active' => true]);
    $otherProvider = Provider::create(['name' => 'Other Inventory Owner', 'status' => 'active']);
    $ownBranch = Branch::create([
        'provider_id' => $provider->id,
        'name' => 'Own Inventory Branch',
        'address' => 'Kampala',
        'latitude' => 0.3476,
        'longitude' => 32.5825,
    ]);
    $otherBranch = Branch::create([
        'provider_id' => $otherProvider->id,
        'name' => 'Other Inventory Branch',
        'address' => 'Entebbe',
        'latitude' => 0.0512,
        'longitude' => 32.4637,
    ]);
    $fuel = Fuel::create(['name' => 'Petrol', 'price_per_litre' => 5000, 'status' => 'available']);
    $ownInventory = BranchFuel::create([
        'branch_id' => $ownBranch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 5000,
        'available_stock_litres' => 20,
        'reserved_stock_litres' => 3,
        'status' => 'active',
    ]);
    $otherInventory = BranchFuel::create([
        'branch_id' => $otherBranch->id,
        'fuel_id' => $fuel->id,
        'price_per_litre' => 5100,
        'available_stock_litres' => 25,
        'status' => 'active',
    ]);

    $this->actingAs($providerAdmin)
        ->put(route('provider.providers.fuels.update', [$provider, $ownInventory]), [
            'price_per_litre' => '5200',
            'available_stock_litres' => '18.000',
            'status' => 'active',
        ])
        ->assertRedirect(route('provider.providers.fuels.index', $provider, absolute: false));

    $this->put(route('provider.providers.fuels.update', [$provider, $otherInventory]), [
        'price_per_litre' => '1',
        'available_stock_litres' => '0',
        'status' => 'inactive',
    ])->assertNotFound();

    $this->get(route('provider.providers.fuels.edit', [$provider, $otherInventory]))
        ->assertNotFound();

    $this->assertDatabaseHas('branch_fuel', [
        'id' => $ownInventory->id,
        'price_per_litre' => 5200,
        'available_stock_litres' => 18,
        'reserved_stock_litres' => 3,
    ]);
    $this->assertDatabaseHas('branch_fuel', [
        'id' => $otherInventory->id,
        'price_per_litre' => 5100,
        'available_stock_litres' => 25,
        'status' => 'active',
    ]);
});
