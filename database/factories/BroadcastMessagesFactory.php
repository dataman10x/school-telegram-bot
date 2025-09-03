<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BroadcastMessages>
 */
class BroadcastMessagesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $current = Carbon::parse(now());
        
        $type = config('constants.broadcast_types.notice');
        return [
            'type' => $type,
            'label' => fake()->realText(70),
            'detail' => fake()->realText(250),
            'start_at' => now(),
            'end_at' => $current->addMinutes(10),
            'can_repeat' => true,
            'admin_id' => null,
        ];
    }
}
