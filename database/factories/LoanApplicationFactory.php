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
    public function definition()
    {
        return [
            'member_id' => Member::factory(),
            'loan_type_id' => LoanType::factory(),
            'product_id' => Product::factory(),
            'applied_amount' => fake()->numberBetween(10000, 100000),
            'term_months' => fake()->randomElement([12, 18, 24, 36]),
            'application_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'purpose' => fake()->sentence(),
        ];
    }

    public function approved()
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'approved',
            'processed_by' => User::factory()->admin(),
        ]);
    }
}
