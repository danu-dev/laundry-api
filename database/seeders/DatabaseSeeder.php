<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // We will seed the database using factories in specific seeders or here
        // Seed initial admin user
        $this->call([UserSeeder::class]);
        $this->call([
            //
        ]);
    }
}
