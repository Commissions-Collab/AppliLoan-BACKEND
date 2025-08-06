<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'member_number' => 'MEM-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'full_name' => fake()->name(),
            'phone_number' => fake()->phoneNumber(),
            'street_address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'tin_number' => fake()->numerify('###-###-###-###'),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'place_of_birth' => fake()->city(),
            'age' => fake()->numberBetween(18, 65),
            'dependents' => fake()->numberBetween(0, 5),
            'employer' => fake()->company(),
            'position' => fake()->jobTitle(),
            'monthly_income' => fake()->numberBetween(15000, 100000),
            'other_income' => fake()->numberBetween(0, 50000),
            'monthly_disposable_income_range' => fake()->randomElement(['0-5000', '5001-10000', '10001-20000', '20001+']),
            'status' => fake()->randomElement(['active', 'inactive', 'suspended']),
        ];
    }
}
