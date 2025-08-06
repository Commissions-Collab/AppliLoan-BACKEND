<?php

namespace Database\Factories;

use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $principal = fake()->numberBetween(10000, 100000);
        $termMonths = fake()->randomElement([12, 18, 24, 36]);
        $interestRate = fake()->randomFloat(2, 8, 24);
        $monthlyPayment = ($principal * (1 + ($interestRate / 100))) / $termMonths;
        
        $applicationDate = fake()->dateTimeBetween('-2 years', '-6 months');
        $approvalDate = fake()->dateTimeBetween($applicationDate, $applicationDate->format('Y-m-d') . ' +30 days');
        $releaseDate = fake()->dateTimeBetween($approvalDate, $approvalDate->format('Y-m-d') . ' +15 days');
        $maturityDate = (clone $releaseDate)->modify("+{$termMonths} months");

        return [
            'loan_application_id' => LoanApplication::factory()->approved(),
            'loan_number' => 'LN-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'principal_amount' => $principal,
            'monthly_payment' => round($monthlyPayment, 2),
            'interest_rate' => $interestRate,
            'term_months' => $termMonths,
            'application_date' => $applicationDate,
            'approval_date' => $approvalDate,
            'release_date' => $releaseDate,
            'maturity_date' => $maturityDate,
            'approved_by' => User::factory()->admin(),
            'purpose' => fake()->sentence(),
            'status' => fake()->randomElement(['active', 'closed', 'defaulted']),
        ];
    }
}
