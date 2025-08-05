<?php

namespace Database\Factories;

use App\Models\LoanType;
use App\Models\Member;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanApplication>
 */
class LoanApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker()->randomElement(['pending', 'approved', 'rejected']);
        return [
            'member_id' => Member::factory(),
            'loan_type_id' => LoanType::factory(),
            'item_name' => Product::factory(),
            'applied_amount' => $this->faker->numberBetween(10000, 500000),
            'term_months' => $this->faker->randomElement([12, 18, 24, 36, 48, 60]),
            'application_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'status' => $status,
            'processed_by' => $status !== 'pending' ? User::factory() : null,
        ];
    }
}
