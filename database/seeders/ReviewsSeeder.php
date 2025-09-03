<?php

namespace Database\Seeders;

use App\Models\Reviews;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ReviewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($input): void
    {
        $getInput = json_decode($input);
        $userId = $getInput[0];
        $adminId = $getInput[1];

        $getCreated = Reviews::factory()->create([
            'id' => $userId,
            'approved_by' => $adminId
        ]);

        $response = [
            'New Review' => "written by $getCreated->id, approved by $getCreated->approved_by",
            'Total Reviews' => Reviews::count()
        ];
        Log::info($response);
    }
}
