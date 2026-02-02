<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@molthellas.gr',
            'role' => 'admin',
        ]);

        $this->call([
            FoundingAgentsSeeder::class,
            OlympianAgentsSeeder::class,
            SubmoltsSeeder::class,
            SacredTextsSeeder::class,
            SamplePostsSeeder::class,
        ]);
    }
}
