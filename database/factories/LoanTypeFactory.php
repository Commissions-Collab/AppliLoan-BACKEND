<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanType>
 */
class LoanTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'type_name' => fake()->randomElement([
                'Appliance Loan',
                'Personal Loan',
                'Emergency Loan',
                'Housing Loan',
                'Education Loan'
            ]),
            'description' => fake()->paragraph(),
            'min_amount' => fake()->numberBetween(5000, 10000),
            'max_amount' => fake()->numberBetween(50000, 500000),
            'interest_rate' => fake()->randomFloat(2, 8, 24),
            'max_term_months' => fake()->randomElement([12, 24, 36, 48]),
            'collateral_required' => fake()->boolean(30),
        ];
    }
}
