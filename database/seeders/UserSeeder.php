<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Alex Rivera', 'email' => 'alex@example.com'],
            ['name' => 'Mia Chen', 'email' => 'mia@example.com'],
            ['name' => 'Daniel Cole', 'email' => 'daniel@example.com'],
            ['name' => 'Sara Kim', 'email' => 'sara@example.com'],
            ['name' => 'Liam Osei', 'email' => 'liam@example.com'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole('customer')) {
                $user->assignRole('customer');
            }
        }
    }
}
