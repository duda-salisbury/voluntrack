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
        // Seed locations first
        $this->call(LocationSeeder::class);

        // Create admin user
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@voluntrack.test',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);
    }
}
