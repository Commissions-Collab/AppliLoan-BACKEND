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
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'due_date' => $this->faker->dateTimeBetween('now', '+5 years'),
            'amount_due' => $this->faker->numberBetween(1000, 15000),
            'status' => $this->faker->randomElement(['unpaid', 'paid', 'overdue']),
        ];
    }
}
