<?php

namespace Database\Seeders;

use App\Models\Admins;
use App\Models\Reviews;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class RunReviews extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = $this->command->ask('Enter User ID of User to add review: ');
        $adminId = $this->command->ask('Enter User ID of Admin to approve review: ');

        $errors = [];
        if(is_null(User::find($userId))) {
            array_push($errors, "$adminId is not found in Users");
        }
        if(is_null(Admins::find($adminId))) {
            array_push($errors, "$adminId is not in Admins");
        }
        if(!is_null(Reviews::find($userId))) {
            array_push($errors, "$userId already written a Review");
        }

        if(count($errors) > 0) {
            $getErrors = implode('; ', $errors);
            Log::error("ERRORS: $getErrors");
            exit();
        }
        
        try {
            $inputArray = [
                $userId,
                $adminId
            ];
            $input = json_encode($inputArray);

            $this->call(ReviewsSeeder::class, false, compact('input'));
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
