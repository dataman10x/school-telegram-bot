<?php

namespace Database\Seeders;

use App\Models\Admins;
use App\Models\Guardians;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class RunGuardians extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $response = null;

        $adminId = $this->command->ask('Enter User ID of the Admin granting authorization: ');
        $userId = $this->command->ask('Enter User ID of User to assign Guardian Role: ');

        $errors = [];
        if(is_null(User::find($userId))) {
            array_push($errors, "$userId is not found in Users");
        }
        if(is_null(Admins::find($adminId))) {
            array_push($errors, "$adminId is not found in Admins");
        }
        if(!is_null(Guardians::find($adminId))) {
            array_push($errors, "$userId is already in Guardians");
        }
        if(count($errors) > 0) {
            $getErrors = implode('; ', $errors);
            Log::error("ERRORS: $getErrors");
            exit();
        }

        try {
            $inputArray = [
                $adminId,
                $userId
            ];
            $input = json_encode($inputArray);

            $this->call(GuardiansSeeder::class, false, compact('input'));

            $response = "$userId (guardian) was added by $adminId";
        } catch (\Throwable $th) {
            $response = $th;
            Log::info($response);
        }
    }
}
