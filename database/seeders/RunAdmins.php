<?php

namespace Database\Seeders;

use App\Models\Admins;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class RunAdmins extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $superadminRoleName = config('constants.user_roles.superadmin');
        $superAdmin = User::where('role', $superadminRoleName)->first();
        $response = null;

        $adminId = $this->command->ask('Enter User ID of User to assign Admin Role: ');
        $superAdminId = $superAdmin->id??null;

        $errors = [];
        if(is_null(User::find($adminId))) {
            array_push($errors, "$adminId is not found in Users");
        }
        if(!is_null(Admins::find($adminId))) {
            array_push($errors, "$adminId is already in Admins");
        }
        if(is_null($superAdminId)) {
            array_push($errors, "Super Admin does not exist yet");
        }
        if(count($errors) > 0) {
            $getErrors = implode('; ', $errors);
            Log::error("ERRORS: $getErrors");
            exit();
        }
        
        try {
            $inputArray = [
                $superAdminId,
                $adminId
            ];
            $input = json_encode($inputArray);

            $this->call(AdminsSeeder::class, false, compact('input'));
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
