<?php

namespace Database\Seeders;

use App\Models\BranchFuel;
use App\Models\Driver;
use App\Models\Fuel;
use App\Models\Order;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $customers = User::query()
            ->whereIn('email', [
                'customer1@gmail.com',
                'customer2@gmail.com',
                'customer3@gmail.com',
                'customer4@gmail.com',
                'customer5@gmail.com',
            ])
            ->orderBy('email')
            ->get();
        $fuels = Fuel::query()->orderBy('id')->get();

        foreach ($customers as $index => $customer) {
            $order = $this->seedOrder(
                'FD'.(1001 + $index),
                'FD'.['234567', '234568', '234569', '23456A', '23456B'][$index % 5],
                [
                    'user_id' => $customer->id,
                    'delivery_address' => 'Kampala, Nakawa',
                    'total' => 0,
                ],
            );

            $total = 0;
            foreach ($fuels->slice($index % 3, 2) as $fuel) {
                $order->fuels()->syncWithoutDetaching([
                    $fuel->id => [
                        'quantity_litres' => 10,
                        'unit_price' => $fuel->price_per_litre,
                    ],
                ]);

                $total += 10 * $fuel->price_per_litre;
            }

            $order->update(['total' => $total]);
        }

        $customer = User::query()->where('email', 'customer1@gmail.com')->firstOrFail();
        $provider = Provider::query()->where('name', 'FuelDrop Demo Provider')->firstOrFail();
        $branch = $provider->branches()->where('name', 'Kampala Central')->firstOrFail();
        $driverUser = User::query()->where('email', 'driver1@gmail.com')->firstOrFail();
        $driver = Driver::query()->whereBelongsTo($driverUser, 'user')->firstOrFail();
        $truck = $provider->trucks()->where('plate', 'UAA 001A')->firstOrFail();
        $fuel = Fuel::query()->where('name', 'Petrol')->firstOrFail();
        $branchFuel = BranchFuel::query()
            ->where('branch_id', $branch->id)
            ->where('fuel_id', $fuel->id)
            ->firstOrFail();
        $quantityLitres = 20;

        $deliveryOrder = $this->seedOrder(
            'FD-DEMO-DELIVERY',
            'FDDEL234',
            [
                'user_id' => $customer->id,
                'provider_id' => $provider->id,
                'branch_id' => $branch->id,
                'driver_id' => $driver->id,
                'truck_id' => $truck->id,
                'total' => $quantityLitres * $branchFuel->price_per_litre,
                'delivery_address' => 'Kampala Central, Kampala',
                'status' => 'pending',
            ],
        );

        $deliveryOrder->fuels()->sync([
            $fuel->id => [
                'branch_fuel_id' => $branchFuel->id,
                'quantity_litres' => $quantityLitres,
                'unit_price' => $branchFuel->price_per_litre,
            ],
        ]);

        $secondCustomer = User::query()->where('email', 'customer2@gmail.com')->firstOrFail();
        $secondDriverUser = User::query()->where('email', 'driver2@gmail.com')->firstOrFail();
        $secondDriver = Driver::query()->whereBelongsTo($secondDriverUser, 'user')->firstOrFail();
        $secondTruck = $provider->trucks()->where('plate', 'UAB 002B')->firstOrFail();
        $secondFuel = Fuel::query()->where('name', 'Diesel')->firstOrFail();
        $secondBranchFuel = BranchFuel::query()
            ->where('branch_id', $branch->id)
            ->where('fuel_id', $secondFuel->id)
            ->firstOrFail();
        $secondQuantityLitres = 20;

        $secondDeliveryOrder = $this->seedOrder(
            'FD-DEMO-DELIVERY-2',
            'FDFUE234',
            [
                'user_id' => $secondCustomer->id,
                'provider_id' => $provider->id,
                'branch_id' => $branch->id,
                'driver_id' => $secondDriver->id,
                'truck_id' => $secondTruck->id,
                'total' => $secondQuantityLitres * $secondBranchFuel->price_per_litre,
                'delivery_address' => 'Kampala Central, Kampala',
                'status' => 'pending',
            ],
        );

        $secondDeliveryOrder->fuels()->sync([
            $secondFuel->id => [
                'branch_fuel_id' => $secondBranchFuel->id,
                'quantity_litres' => $secondQuantityLitres,
                'unit_price' => $secondBranchFuel->price_per_litre,
            ],
        ]);
    }

    /** Keep already-seeded historical references; use a standard reference for fresh seed data. */
    private function seedOrder(string $historicalReference, string $standardReference, array $attributes): Order
    {
        $order = Order::query()->where('order_number', $historicalReference)->first()
            ?? Order::query()->where('order_number', $standardReference)->first()
            ?? new Order(['order_number' => $standardReference]);

        $order->fill($attributes);
        $order->save();

        return $order;
    }
}
