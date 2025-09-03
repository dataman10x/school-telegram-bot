<?php

namespace Database\Seeders;

use App\Models\Admins;
use App\Models\AngelsLevels;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class RunAngels extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $limit = $this->command->ask('Enter number of Angels to generate: ');
        
        if(Admins::count() == 0) {
            Log::warning("No Admin account was found to create Angels");
            exit();
        }

        // check if level exist
        $getLevel = AngelsLevels::first();
        if(is_null($getLevel)) {
            Log::warning("No Level for Angels was found to create Angels");
            exit();
        }

        // create Angels
        $levelId = $getLevel->id;

        $input = json_encode([$limit, $levelId]);
        $this->call(AngelsSeeder::class, false, compact('input'));
    }
}
