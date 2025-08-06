<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanPayment>
 */
class LoanPaymentFactory extends Factory
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
            'schedule_id' => LoanSchedule::factory(),
            'payment_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'amount_paid' => fake()->numberBetween(1000, 5000),
            'remaining_balance' => fake()->numberBetween(0, 100000),
            'payment_method' => fake()->randomElement(['cash', 'check', 'bank_transfer']),
            'receipt_number' => 'OR-' . fake()->unique()->numerify('######'),
            'received_by' => User::factory()->admin(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
