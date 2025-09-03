<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Angels;
use App\Models\AngelsLevels;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class RunUserRollbacks extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $baseRole = config('constants.user_roles.tester');
        $baseLevel = config('constants.default_angel_levels.tester');

        $getRemoveUsers = User::where('role', '=', $baseRole)->forceDelete();
        $getRemoveLevels = AngelsLevels::where('label', 'like', "$baseLevel-%")->delete();
            
        $response = [
            'Removed Users' => $getRemoveUsers,
            'Removed Angels Levels' => $getRemoveLevels,
            'Total Users' => User::count(),
            'Total Angels' => Angels::count()
        ];
        Log::info($response);
    }
}
