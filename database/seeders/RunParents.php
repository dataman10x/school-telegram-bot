<?php

namespace Database\Seeders;

use App\Models\Guardians;
use App\Models\Parents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class RunParents extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $response = null;

        $guardianId = $this->command->ask('Enter User ID of the Guardian granting authorization: ');
        $userId = $this->command->ask('Enter User ID of User to assign Parent Role: ');

        $errors = [];
        if(is_null(User::find($userId))) {
            array_push($errors, "$userId is not found in Users");
        }
        if(is_null(Guardians::find($guardianId))) {
            array_push($errors, "$guardianId is not found in Guardians");
        }
        if(!is_null(Parents::find($userId))) {
            array_push($errors, "$userId is already in Parents");
        }
        if(count($errors) > 0) {
            $getErrors = implode('; ', $errors);
            Log::error("ERRORS: $getErrors");
            exit();
        }

        try {
            $inputArray = [
                $guardianId,
                $userId
            ];
            $input = json_encode($inputArray);

            $this->call(ParentsSeeder::class, false, compact('input'));

            $response = "$userId (parent) was added by $guardianId";
        } catch (\Throwable $th) {
            $response = $th;
            Log::info($response);
        }
    }
}
