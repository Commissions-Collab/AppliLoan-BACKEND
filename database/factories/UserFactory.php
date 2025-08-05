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
        return [
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => fake()->optional(0.8)->dateTime(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => fake()->randomElement(['member']),
            'otp' => fake()->optional(0.1)->numberBetween(100000, 999999),
            'is_verified' => fake()->boolean(95),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'email' => 'admin@school.com',
            'is_verified' => true,
        ]);
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
