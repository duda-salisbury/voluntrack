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
        // 1. Create the default organization
        $this->call(OrganizationSeeder::class);

        // 2. Seed locations (attached to default org via OrganizationSeeder back-fill,
        //    or pass the org to LocationSeeder directly for a fresh install)
        $this->call(LocationSeeder::class);

        // 3. Create admin user
        User::factory()->create([
            'name'     => 'Admin',
            'email'    => 'admin@voluntrack.test',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        // 4. Back-fill: ensure all data is associated with the default org
        $this->call(OrganizationSeeder::class);
    }
}
