<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admins>
 */
class AdminsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $baseRole = config('constants.user_roles.tester');
        // $id = fake()->unique()->randomDigit;
        $id = fake()->unixTime();

        return [
            'id' => hashid()->encode($id),
            'detail' => fake()->paragraphs,
            'approved_by' => $baseRole,
        ];
    }
}
