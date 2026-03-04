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
        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        // Create PM user
        User::factory()->create([
            'name' => 'PM User',
            'email' => 'pm@example.com',
            'role' => 'pm',
        ]);

        // Create engineer user
        User::factory()->create([
            'name' => 'Engineer User',
            'email' => 'engineer@example.com',
            'role' => 'engineer',
        ]);

        // Create viewer user
        User::factory()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'role' => 'viewer',
        ]);

        // Create additional test users
        User::factory(5)->create();
    }
}
