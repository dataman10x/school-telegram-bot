<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class RunUsers extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $limit = $this->command->ask('Enter limit of Testers');
        $response = $this->call(UsersSeeder::class, false, compact('limit'));

        Log::info($response);
    }
}
