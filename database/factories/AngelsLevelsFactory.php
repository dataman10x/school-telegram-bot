<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AngelsLevels>
 */
class AngelsLevelsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $baseLevel = config('constants.default_angel_levels.tester');
        $id = fake()->unixTime();
        $level = "$baseLevel-$id";

        return [
            'label' => $level,
            'detail' => fake()->paragraph
        ];
    }
}
