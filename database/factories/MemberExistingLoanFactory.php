<?php

namespace Database\Factories;

use App\Models\LoanType;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MemberExistingLoan>
 */
class MemberExistingLoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalAmount = $this->faker->numberBetween(10000, 200000);
        $outstanding = $this->faker->numberBetween(1000, $originalAmount);

        return [
            'member_id' => Member::factory(),
            'loan_type_id' => LoanType::factory(),
            'creditor_name' => $this->faker->randomElement([
                'BDO',
                'BPI',
                'Metrobank',
                'Landbank',
                'SSS',
                'Pag-IBIG',
                'Rizal Commercial Banking Corp',
                'Security Bank'
            ]),
            'date_granted' => $this->faker->dateTimeBetween('-5 years', '-1 month'),
            'original_amount' => $originalAmount,
            'outstanding_balance' => $outstanding,
            'monthly_installment' => $this->faker->numberBetween(1000, 15000),
            'status' => $this->faker->randomElement(['active', 'paid', 'defaulted']),
        ];
    }
}
