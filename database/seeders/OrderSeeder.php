<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Fuel;
use App\Models\Order;
use App\Models\User;


class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //customer found by their email pattern
        $customer = User::where('email', 'like', 'customer%@gmail.com')->get();
        $fuels = Fuel::all();

        foreach($customer as $i => $customer){
            //one order per customer, found by its unique order number
            $order = Order::firstOrCreate(
                ['order_number'=>'FD' . (1001 + $i)],
                ['user_id'=>$customer->id, 'delivery_address'=>'Kampala, Nakawa', 'total'=>0]
            );
            //attach two fuels per order
            $total = 0;
            foreach($fuels->slice($i % 3, 2) as $fuel){
                $order->fuels()->syncWithoutDetaching([
                    $fuel->id=>['quantity_litres'=>10, 'unit_price'=>$fuel->price_per_litre],
                ]);
                $total += 10 * $fuel->price_per_litre;
            }
            $order->update(['total'=> $total]);
        }
        
    }
}
