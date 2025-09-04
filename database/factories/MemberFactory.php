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
    'member_number' => strtoupper($this->faker->bothify('MBR-####')),
    'user_id' => User::factory(), // Creates a user if none exists
    'full_name' => $this->faker->name(),
    'phone_number' => $this->faker->numerify('09#########'),
    'address' => $this->faker->streetAddress(), // merged with city/province
    'date_of_birth' => $this->faker->date('Y-m-d', '-18 years'),
    'place_of_birth' => $this->faker->city(),
    'age' => $this->faker->numberBetween(18, 65),
    'civil_status' => $this->faker->randomElement(['single', 'married', 'widowed', 'separated']),
    'religion' => $this->faker->randomElement(['Catholic', 'Christian', 'Muslim', 'Other']),
    'tin_number' => $this->faker->numerify('#########'),
    'employer' => $this->faker->company(),
    'position' => $this->faker->jobTitle(),
    'monthly_income' => $this->faker->randomFloat(2, 5000, 50000),
    'other_income' => $this->faker->word(),
    'dependents' => $this->faker->numberBetween(0, 5),
    'share_capital' => 20.00,
    'fixed_deposit' => $this->faker->randomFloat(2, 0, 10000),
    'seminar_date' => $this->faker->date('Y-m-d'),
    'venue' => $this->faker->company() . ' Hall',
    'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
    
    // File fields (stored as fake paths for testing)
    'brgy_clearance' => $this->faker->optional()->filePath(),
    'birth_cert' => $this->faker->optional()->filePath(),
    'certificate_of_employment' => $this->faker->optional()->filePath(),
    'applicant_photo' => $this->faker->optional()->imageUrl(),
    'valid_id' => $this->faker->optional()->filePath(),
    ];
    }
}
