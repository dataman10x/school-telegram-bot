<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Angels>
 */
class AngelsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $levelId = 1;
        $id = fake()->unixTime();
        $refId = hashid()->encode($id);

        return [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'detail' => fake()->paragraphs,
            'level_id' => $levelId,
            'ref' => $refId
        ];
    }
}
