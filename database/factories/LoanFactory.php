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
    public function definition(): array
    {
        $principalAmount = $this->faker->numberBetween(10000, 100000);
        $interestRate = $this->faker->randomFloat(2, 6.00, 10.00);
        $termMonths = $this->faker->randomElement([12, 18, 24, 36, 48, 60]);
        $monthlyPayment = $this->calculateMonthlyPayment($principalAmount, $interestRate, $termMonths);

        $applicationDate = $this->faker->dateTimeBetween('-2 years', '-1 month');
        $approvalDate = $this->faker->dateTimeBetween('-2 years', '1 month');
        $releaseDate = $this->faker->dateTimeBetween($approvalDate, 'now');
        $maturityDate = (clone $releaseDate)->modify("+{$termMonths} months");

        return [
            'loan_application_id' => LoanApplication::factory(),
            'loan_number' => 'LN-' . $this->faker->unique()->numerify('########'),
            'principal_amount' => $principalAmount,
            'monthy_payment' => $monthlyPayment,
            'interest_rate' => $interestRate,
            'term_months' => $termMonths,
            'application_date' => $applicationDate,
            'approval_date' => $approvalDate,
            'release_date' => $releaseDate,
            'maturity_date' => $maturityDate,
            'approved_by' => User::factory()->admin()->create()->id,
            'purpose' => $this->faker->sentence(8),
            'status' => $this->faker->randomElement(['active', 'closed', 'defaulted']),
        ];
    }

    private function calculateMonthlyPayment($principal, $annualRate, $termMonths)
    {
        $monthlyRate = $annualRate / 100 / 12;
        if ($monthlyRate == 0) return $principal / $termMonths;

        return $principal * ($monthlyRate * pow(1 + $monthlyRate, $termMonths)) /
            (pow(1 + $monthlyRate, $termMonths) - 1);
    }
}
