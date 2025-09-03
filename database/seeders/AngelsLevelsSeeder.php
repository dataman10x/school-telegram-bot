<?php

namespace Database\Seeders;

use App\Models\AngelsLevels;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AngelsLevelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($getLimit): void
    {
        $baseLevel = config('constants.default_angel_levels.tester');

        $getCreated = AngelsLevels::factory($getLimit)->create();

        $response = [
            "Seeded Angel Levels (prefix: $baseLevel)" => $getCreated->count(),
            'Total Angel Levels' => AngelsLevels::count()
        ];
        Log::info($response);
    }
}
