<?php

namespace Database\Factories;

use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanSchedule>
 */
class LoanScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'loan_id' => Loan::factory(),
            'due_date' => fake()->dateTimeBetween('now', '+2 years'),
            'amount_due' => fake()->numberBetween(1000, 5000),
            'principal_amount' => fake()->numberBetween(800, 4000),
            'interest_amount' => fake()->numberBetween(200, 1000),
            'status' => fake()->randomElement(['unpaid', 'paid', 'overdue']),
        ];
    }
}
