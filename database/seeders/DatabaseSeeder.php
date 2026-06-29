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
        // Create roles and permissions first
        $this->call(RolePermissionSeeder::class);

        $admin = User::firstOrCreate(
        ['email' => 'admin@gmail.com'], 
        [
            'name' => 'Admin',
            'password' => bcrypt('password'), 
            'email_verified_at' => now(),
        ]
    );

        if (!$admin->hasRole('admin')) {
        $admin->assignRole('admin');
    }

        // Feature demo data
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            SizeSeeder::class,
            ColorSeeder::class,
            ProductSeeder::class,
            SettingSeeder::class,
        ]);
    }
}

