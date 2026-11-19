<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchFuel;
use App\Models\Driver;
use App\Models\Fuel;
use App\Models\Provider;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoProviderSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $providerAdmin = User::query()->where('email', 'admin@fueldrop.test')->firstOrFail();

        $provider = Provider::firstOrCreate(
            ['name' => 'FuelDrop Demo Provider'],
            [
                'admin_user_id' => $providerAdmin->id,
                'phone' => '0700111222',
                'email' => $providerAdmin->email,
                'status' => 'active',
            ],
        );
        if ($provider->admin_user_id === null) {
            $provider->update(['admin_user_id' => $providerAdmin->id]);
        }

        $provider->users()->syncWithoutDetaching([
            $providerAdmin->id => ['is_active' => true],
        ]);

        $branch = Branch::firstOrCreate(
            ['provider_id' => $provider->id, 'name' => 'Kampala Central'],
            [
                'address' => 'Kampala Central, Kampala',
                'latitude' => 0.3476,
                'longitude' => 32.5825,
                'service_radius_metres' => 25000,
                'status' => 'active',
            ],
        );

        foreach (Fuel::query()->get() as $fuel) {
            BranchFuel::firstOrCreate(
                ['branch_id' => $branch->id, 'fuel_id' => $fuel->id],
                [
                    'price_per_litre' => $fuel->price_per_litre,
                    'available_stock_litres' => 500,
                    'reserved_stock_litres' => 0,
                    'status' => 'active',
                ],
            );
        }

        $drivers = [
            ['driver1@gmail.com', 'UAA 001A', 'Demo Delivery Truck'],
            ['driver2@gmail.com', 'UAB 002B', 'Demo Delivery Truck 2'],
        ];

        foreach ($drivers as [$email, $plate, $name]) {
            $driverUser = User::query()->where('email', $email)->firstOrFail();
            $truck = Truck::firstOrCreate(
                ['plate' => $plate],
                [
                    'provider_id' => $provider->id,
                    'branch_id' => $branch->id,
                    'name' => $name,
                    'capacity_litres' => 1000,
                    'status' => 'available',
                ],
            );

            Driver::firstOrCreate(
                ['user_id' => $driverUser->id],
                [
                    'provider_id' => $provider->id,
                    'branch_id' => $branch->id,
                    'truck_plate' => $truck->plate,
                    'available_status' => 'available',
                ],
            );
        }
    }
}
