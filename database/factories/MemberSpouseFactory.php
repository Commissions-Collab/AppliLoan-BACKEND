<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MemberSpouse>
 */
class MemberSpouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $birthDate = $this->faker->dateTimeBetween('-65 years', '-18 years');
        $age = now()->diffInYears($birthDate);
        $monthlyIncome = $this->faker->numberBetween(12000, 60000);
        $otherIncome = $this->faker->numberBetween(5000, 20000);
        $totalIncome = $monthlyIncome + ($otherIncome ?? 0);
        $expenses = $this->faker->numberBetween(6000, $totalIncome * 0.6);

        $disposable = $totalIncome - $expenses;

        if ($disposable <= 5000) {
            $range = '0-5000';
        } elseif ($disposable <= 10000) {
            $range = '5001-10000';
        } elseif ($disposable <= 20000) {
            $range = '10001-20000';
        } else {
            $range = '20001+';
        }
        return [
            'member_id' => Member::factory(),
            'full_name' => $this->faker->name(),
            'street_address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'province' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'tin_number' => $this->faker->numerify('###-###-###-###'),
            'contact_number' => $this->faker->phoneNumber(),
            'date_of_birth' => $birthDate,
            'place_of_birth' => $this->faker->city() . ', ' . $this->faker->state(),
            'age' => $age,
            'dependents' => $this->faker->optional(0.5)->numberBetween(0, 3),
            'employer' => $this->faker->company(),
            'position' => $this->faker->jobTitle(),
            'monthly_income' => $monthlyIncome,
            'other_income' => $otherIncome,
            'monthly_disposable_income_range' => $range,
        ];
    }
}
