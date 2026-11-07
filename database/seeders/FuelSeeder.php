<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Fuel;

class FuelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fuels = [
            ['name'=>'Petrol', 'price_per_litre'=>5200],
            ['name'=>'Diesel', 'price_per_litre'=>4200],
            ['name'=>'Paraffin', 'price_per_litre'=>3200],
        ];

        foreach ($fuels as $fuel){
            Fuel::firstOrCreate(
                ['name'=>$fuel['name']],
                ['price_per_litre'=>$fuel['price_per_litre']]

            );
        }

        
    }
}
