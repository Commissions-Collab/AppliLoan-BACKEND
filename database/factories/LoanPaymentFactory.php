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
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'schedule_id' => LoanSchedule::factory(),
            'payment_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'amount_paid' => $this->faker->numberBetween(1000, 15000),
            'remaining_balance' => $this->faker->numberBetween(1000, 15000),
            'payment_method' => $this->faker->randomElement(['cash', 'check', 'bank_transfer']),
            'receipt_number' => $this->faker->numerify('###-###-###'),
            'receive_id' => 1,
        ];
    }
}
