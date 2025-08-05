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
    public function definition(): array
    {
        $loanTypes = [
            ['name' => 'Regular Loan', 'rate' => 12.00, 'max_term' => 60],
            ['name' => 'Appliance Loan', 'rate' => 14.00, 'max_term' => 48],
            ['name' => 'Emergency Loan', 'rate' => 10.50, 'max_term' => 12],
            ['name' => 'Salary Loan', 'rate' => 9.75, 'max_term' => 24],
        ];

        $type = $this->faker->randomElement($loanTypes);

        $min = $this->faker->numberBetween(5000, 50000);
        $max = $min + $this->faker->numberBetween(50000, 200000);

        return [
            'type_name' => $type['name'],
            'description' => $this->faker->sentence(10),
            'min_amount' => $min,
            'max_amount' => $max,
            'interest_rate' => $type['rate'],
            'max_term_months' => $type['max_term'],
            'collateral_required' => $this->faker->boolean(30), // 30% chance to require collateral
        ];
    }
}
