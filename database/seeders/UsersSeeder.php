<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class UsersSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run($limit): void
    {
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $maxLimit = 2000;
        $totalCreated = 0;
        $batches = 0;
        $balance = 0;
        if($limit > $maxLimit) {
            $batches = intdiv($limit, $maxLimit);
            $balance = fmod($limit, $maxLimit);
        }

        while ($totalCreated < $limit) {
            $setLimit = $batches > 0?$maxLimit: $limit;
            if($totalCreated >= ($batches * $maxLimit) && $balance > 0) {
                $setLimit = $balance;
            }

            $getCreated = User::factory($setLimit)->create();
            $totalCreated += $getCreated->count();
            Log::info("INCREMENTAL BATCH ($maxLimit): $totalCreated");
        }

        $response = [
            'Seeded Users (w/ role: Tester)' => $totalCreated,
            'Total Users' => User::count()
        ];
        Log::info($response);
    }
}
