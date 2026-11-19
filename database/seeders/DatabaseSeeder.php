<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
<<<<<<< Updated upstream
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
=======
        $this->call([
            RolePermissionSeeder::class,
            FuelSeeder::class,
            UserSeeder::class,
>>>>>>> Stashed changes
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call([
                DemoProviderSeeder::class,
                OrderSeeder::class,
            ]);
        }
    }
}
