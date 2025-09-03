<?php

namespace Database\Seeders;

use App\Models\BroadcastMessages;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class BroadcastMessagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($input): void
    {
        $getInput = json_decode($input);
        $limit = $getInput[0];
        $type = $getInput[1];
        $canRepeat = $getInput[2] == 'yes'? true: false;
        $startAt = $getInput[3];
        $endAt = $getInput[4];
        $adminId = $getInput[5];

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

            $getCreated = BroadcastMessages::factory($setLimit)->create([
                'type' => $type,
                'start_at' => $getStartAt,
                'end_at' => $getEndAt,
                'can_repeat' => $canRepeat,
                'admin_id' => $adminId
            ]);

            $totalCreated += $getCreated->count();
            Log::info("INCREMENTAL BATCH ($maxLimit): $totalCreated");
        }

        $getCanRepeatStr = $canRepeat?'Yes':'No';
        $response = [
            "Seeded Broadcasts (w/ can repeat: $getCanRepeatStr | Type: $type)" => $totalCreated,
            'Total Broadcasts' => BroadcastMessages::count()
        ];
        Log::info($response);
    }
}
