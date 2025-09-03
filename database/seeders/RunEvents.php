<?php

namespace Database\Seeders;

use App\Models\Conveners;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class RunEvents extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $limit = $this->command->ask('Enter number of of Events: ');
        $lockCode = $this->command->ask('Enter Lock Code of Events OR skip: ');
        $startAt = $this->command->ask('When to activate Events in minutes, now or a number: ');
        $endAt = $this->command->ask('When to deactivate Events in minutes: ');
        $convenerId = $this->command->ask('Enter User ID of Convener to create events: ');

        $errors = [];
        if(is_null(Conveners::find($convenerId))) {
            array_push($errors, "$convenerId is not found in Conveners");
        }

        if(count($errors) > 0) {
            $getErrors = implode('; ', $errors);
            Log::error("ERRORS: $getErrors");
            exit();
        }
        
        try {
            $inputArray = [
                $limit,
                $lockCode,
                $startAt,
                $endAt,
                $convenerId
            ];
            $input = json_encode($inputArray);

            $this->call(EventsSeeder::class, false, compact('input'));
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
