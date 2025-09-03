<?php

namespace Database\Seeders;

use App\Models\Admins;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class RunBroadcasts extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $typeList = config('constants.broadcast_types');
        $limit = $this->command->ask('Enter number of of Broadcasts: ');
        $type = $this->command->ask('Enter type of Broadcast: ');
        $canRepeat = $this->command->ask('Can the Broadcasts repeat? yes or no: ');
        $startAt = $this->command->ask('When to activate broadcasts in minutes, now or a number: ');
        $endAt = $this->command->ask('When to deactivate broadcasts in minutes: ');
        $adminId = $this->command->ask('Enter User ID of Admin to approve review: ');

        $errors = [];
        if(is_null(Admins::find($adminId))) {
            array_push($errors, "$adminId is not found in Users");
        }
        if(is_null(Admins::find($adminId))) {
            array_push($errors, "$adminId is not in Admins");
        }
        if(!array_key_exists($type, $typeList)) {
            array_push($errors, "$type is not a valid broadcast type");
        }

        if(count($errors) > 0) {
            $getErrors = implode('; ', $errors);
            Log::error("ERRORS: $getErrors");
            exit();
        }
        
        try {
            $inputArray = [
                $limit,
                $type,
                $canRepeat,
                $startAt,
                $endAt,
                $adminId
            ];
            $input = json_encode($inputArray);

            $this->call(BroadcastMessagesSeeder::class, false, compact('input'));
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
