<?php

namespace Database\Seeders;

use App\Models\Events;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class EventsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($input): void
    {
        $getInput = json_decode($input);
        $limit = $getInput[0];
        $lockCode = $getInput[1];
        $startAt = $getInput[2];
        $endAt = $getInput[3];
        $convenerId = $getInput[4];

        $getStartAt = now();
        $getEndAt = Carbon::parse(now())->addMinutes(10);

        if(is_int(intval($startAt))) {
            $getStartAt = Carbon::parse(now())->addMinutes($startAt);
        }

        if(is_int(intval($endAt))) {
            $getEndAt = Carbon::parse(now())->addMinutes($endAt);
        }

        $maxLimit = 2000;
        $totalCreated = 0;
        $batches = 0;
        $balance = 0;
        if($limit > $maxLimit) {
            $batches = intdiv($limit, $maxLimit);
            $balance = fmod($limit, $maxLimit);
        }

        while ($totalCreated < $limit) {
            $setLimit = $batches > 0?$maxLimit: $limit;
            if($totalCreated >= ($batches * $maxLimit) && $balance > 0) {
                $setLimit = $balance;
            }

            $getCreated = Events::factory($setLimit)->create([
                'lock_code' => $lockCode,
                'start_at' => $getStartAt,
                'end_at' => $getEndAt,
                'convener_id' => $convenerId
            ]);

            $totalCreated += $getCreated->count();
            Log::info("INCREMENTAL BATCH ($maxLimit): $totalCreated");
        }

        $getLockCodeStr = empty($lockCode)?'None':"$lockCode";
        $response = [
            "Seeded Events (w/ Lock Code: $getLockCodeStr)" => $totalCreated,
            'Total Events' => Events::count()
        ];
        Log::info($response);
    }
}
