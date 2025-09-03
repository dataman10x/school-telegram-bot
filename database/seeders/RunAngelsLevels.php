<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RunAngelsLevels extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $getLimit = $this->command->ask('Enter number of Angels to generate: ');
        
        $this->call(AngelsLevelsSeeder::class, false, compact('getLimit'));
    }
}
