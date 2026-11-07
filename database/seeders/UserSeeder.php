<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            ['name' => 'Admin', 'password' => Hash::make('password')]

        );

        $drivers = [
            ['Driver One', 'driver1@gmail.com', 'UBA 234Q'],
            ['Driver Two', 'driver2@gmail.com', 'UBR 200A'],
        ];
        foreach ($drivers as [$name, $email, $plate]) {
            $user = User::firstOrCreate(
                ['email'=>$email],
                ['name'=>$name, 'password'=>Hash::make('password')]
                
            );
            Driver::firstOrCreate(['user_id'=>$user->id], ['truck_plate'=>$plate]);

        }

        $customers = ['Carey Mawejje', 'Ahonesa Joshua', 'Nakitende Vivian', 'Juuko Louise', 'Namuyiga Aliyah'];
        foreach ($customers as $i =>$name) {
            User::firstOrCreate(
                ['email'=>'customer' . ($i + 1) . '@gmail.com'],
                ['name'=>$name, 'password'=> Hash::make('password')]
            );
        }
    }
}
