<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $baseRole = config('constants.user_roles.tester');
        // $randNumber = fake()->randomDigit;
        $timeFactor = fake()->unixTime();
        $username = fake()->userName();
        // $sha1 = fake()->sha1();
        $lastname = fake()->lastName();
        $name = strtolower($lastname);
        $id = $username . $timeFactor;
        $email = fake()->safeEmail();
        $getEmail = "$username.$email";

        return [
            'id' => $id,
            'name' => $name,
            'username' => $username,
            'firstname' => fake()->firstName(),
            'lastname' => $lastname,
            'phone' => fake()->phoneNumber(),
            'email' => $getEmail,
            'role' => $baseRole,
            // 'email_verified_at' => now(),
            // 'password' => static::$password ??= Hash::make('password'),
            // 'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
