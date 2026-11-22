<?php

namespace Database\Seeders;

use App\Models\Fuel;
use Illuminate\Database\Seeder;

class FuelSeeder extends Seeder
{
    /**
     * Seed the fuel types used by the demo provider and orders.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Petrol', 'price_per_litre' => 5000, 'status' => 'available'],
            ['name' => 'Diesel', 'price_per_litre' => 4200, 'status' => 'available'],
        ] as $fuel) {
            Fuel::firstOrCreate(
                ['name' => $fuel['name']],
                [
                    'price_per_litre' => $fuel['price_per_litre'],
                    'status' => $fuel['status'],
                ],
            );
        }
    }
}
