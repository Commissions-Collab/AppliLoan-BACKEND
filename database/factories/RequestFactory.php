<?php

namespace Database\Factories;

use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequestFactory extends Factory
{
    protected $model = Request::class;

    public function definition(): array
    {
        return [
            'request_to' => User::factory(), // will create a user if none exists
            'member_number' => strtoupper($this->faker->bothify('MBR-####')),
            'full_name' => $this->faker->name(),
            'phone_number' => $this->faker->phoneNumber(),
            'street_address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'province' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'tin_number' => $this->faker->numerify('#########'),
            'date_of_birth' => $this->faker->date('Y-m-d', '-18 years'),
            'place_of_birth' => $this->faker->city(),
            'age' => $this->faker->numberBetween(18, 65),
            'dependents' => $this->faker->numberBetween(0, 5),
            'employer' => $this->faker->company(),
            'position' => $this->faker->jobTitle(),
            'monthly_income' => $this->faker->randomFloat(2, 5000, 50000),
            'other_income' => $this->faker->randomFloat(2, 0, 10000),
            'monthly_disposable_income_range' => $this->faker->randomElement(['0-5000', '5001-10000', '10001-20000', '20001+']),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
        ];
    }
}
