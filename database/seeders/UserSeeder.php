<?php

namespace Database\Seeders;

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
        $admin = config('fueldrop.admin');
        $isDemoEnvironment = app()->environment(['local', 'testing']);

        if (filled($admin['email']) && filled($admin['password'])) {
            $user = User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'] ?: 'Admin',
                    'phone' => $admin['phone'],
                    'password' => Hash::make($admin['password']),
                    'email_verified_at' => now(),
                    'status' => 'active',
                ],
            );

            $user->assignRole('admin');
        } elseif ($isDemoEnvironment) {
            $adminUser = User::firstOrCreate(
                ['email' => 'admin@gmail.com'],
                [
                    'name' => 'Admin',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'status' => 'active',
                ],
            );

            $adminUser->assignRole('admin');
        }

        if (! $isDemoEnvironment) {
            return;
        }

        $demoUsers = [
            ['FuelDrop Provider', 'admin@fueldrop.test', 'provider'],
            ['Driver One', 'driver1@gmail.com', 'driver'],
            ['Driver Two', 'driver2@gmail.com', 'driver'],
            ['Carey Mawejje', 'customer1@gmail.com', 'customer'],
            ['Ahonesa Joshua', 'customer2@gmail.com', 'customer'],
            ['Nakitende Vivian', 'customer3@gmail.com', 'customer'],
            ['Juuko Louise', 'customer4@gmail.com', 'customer'],
            ['Namuyiga Aliyah', 'customer5@gmail.com', 'customer'],
        ];

        foreach ($demoUsers as [$name, $email, $role]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'status' => 'active',
                ],
            );

            $user->assignRole($role);
        }
    }
}
