<?php

namespace Database\Seeders;

use App\Models\Admins;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AdminsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($input): void
    {
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $getInput = json_decode($input);
        $superAdminId = $getInput[0];
        $adminId = $getInput[1];

        $getCreated = Admins::factory()->create([
            'id' => $adminId,
            'approved_by' => $superAdminId,
            'approved_at' => now()
        ]);

        $response = [
            'New Admin' => "$getCreated->id added by $getCreated->approved_by",
            'Total Admins' => Admins::count()
        ];
        Log::info($response);
    }
}
