<?php

namespace Database\Seeders;

use App\Models\Conveners;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ConvenersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($input): void
    {
        $getInput = json_decode($input);
        $approvedById = $getInput[0];
        $userId = $getInput[1];

        $getCreated = Conveners::factory()->create([
            'id' => $userId,
            'approved_by' => $approvedById,
            'approved_at' => now()
        ]);

        $response = [
            'New Convener' => "$getCreated->id added by $getCreated->approved_by",
            'Total Conveners' => Conveners::count()
        ];
        Log::info($response);
    }
}
