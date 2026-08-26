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
        // Admin test account
        User::firstOrCreate(
            ['email' => 'admin@laundry.com'],
            [
                'name' => 'Admin Laundry',
                'password' => Hash::make('password'),
            ]
        );
        
        // You can generate more dummy users via factory if needed:
        // User::factory(10)->create();
    }
}
