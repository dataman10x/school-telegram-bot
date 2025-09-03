<?php

namespace Database\Seeders;

use App\Models\Parents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ParentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($input): void
    {
        $getInput = json_decode($input);
        $approvedById = $getInput[0];
        $userId = $getInput[1];

        $getCreated = Parents::factory()->create([
            'id' => $userId,
            'approved_by' => $approvedById,
            'approved_at' => now()
        ]);

        $response = [
            'New Parent' => "$getCreated->id added by $getCreated->approved_by",
            'Total Parents' => Parents::count()
        ];
        Log::info($response);
    }
}
