<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanPenalty>
 */
class LoanPenaltyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dueDate = $this->faker->dateTimeBetween('-8 months', '-1 month');
        $penaltyDate = $this->faker->dateTimeBetween($dueDate, 'now');
        $daysOverdue = $penaltyDate->diff($dueDate)->days;

        return [
            'loan_id' => Loan::factory(),
            'penalty_rate' => 2.00,
            'penalty_amount' => $this->faker->randomFloat(2, 100, 2000),
            'due_date' => $dueDate,
            'penalty_date' => $penaltyDate,
            'days_overdue' => $daysOverdue,
            'status' => $this->faker->randomElement(['active', 'paid', 'waived']),
            'remarks' => $this->faker->optional()->sentence(),
        ];
    }
}
