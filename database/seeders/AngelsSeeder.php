<?php

namespace Database\Seeders;

use App\Models\Angels;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AngelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($input): void
    {
        $baseLevel = config('constants.default_angel_levels.tester');

        $getInput = json_decode($input);
        $limit = $getInput[0];
        $level = $getInput[1];

        $getCreated = Angels::factory($limit)->create([
            'level_id' => $level
        ]);

        $response = [
            "Seeded Angels (w/ level prefix: $baseLevel)" => $getCreated->count(),
            'Total Angels' => Angels::count()
        ];
        Log::info($response);
    }
}
