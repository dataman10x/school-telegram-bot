<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Events>
 */
class EventsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $current = Carbon::parse(now());
        
        return [
            'title' => fake()->realText(70),
            'detail' => fake()->realText(750),
            'venue' => fake()->address(),
            'lock_code' => null,
            'start_at' => now(),
            'end_at' => $current->addMinutes(10),
            'convener_id' => null,
        ];
    }
}
