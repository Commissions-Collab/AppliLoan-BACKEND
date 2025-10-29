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
            'user_id' => User::factory(),
            'loan_type_id' => LoanType::factory(),
            'product_id' => Product::factory(),
            'user_name' => $this->faker->name(),
            'applied_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'term_months' => $this->faker->numberBetween(6, 60),

            // personal details
            'phone' => $this->faker->phoneNumber(),
            'age' => $this->faker->numberBetween(18, 65),
            'address' => $this->faker->address(),
            'tin_number' => $this->faker->numerify('#########'),
            'employer' => $this->faker->company(),
            'position' => $this->faker->jobTitle(),
            'monthly_income' => $this->faker->randomFloat(2, 10000, 50000),
            'other_income_source' => $this->faker->optional()->word(),
            'monthly_disposable_income' => $this->faker->numberBetween(5000, 20000),
            'birth_month' => $this->faker->monthName(),
            'place_of_birth' => $this->faker->city(),
            'no_of_dependents' => $this->faker->numberBetween(0, 5),

            // estimated expenses
            'education_expense' => $this->faker->randomFloat(2, 1000, 5000),
            'food_expense' => $this->faker->randomFloat(2, 2000, 10000),
            'house_expense' => $this->faker->randomFloat(2, 3000, 15000),
            'transportation_expense' => $this->faker->randomFloat(2, 1000, 7000),

            // amortization details
            'date_granted' => $this->faker->date(),
            'monthly_installment' => $this->faker->randomFloat(2, 1000, 20000),
            'SMPC_regular_loan' => $this->faker->randomFloat(2, 5000, 50000),
            'SMPC_petty_cash_loan' => $this->faker->randomFloat(2, 1000, 10000),
            'total_amortization' => $this->faker->randomFloat(2, 5000, 60000),

            // required documents (fake file paths/URLs)
            'applicant_photo' => $this->faker->imageUrl(200, 200, 'people', true, 'Applicant'),
            'certificate_of_employment' => $this->faker->filePath(),
            'bragy_certificate' => $this->faker->filePath(),
            'valid_id_front' => $this->faker->filePath(),
            'valid_id_back' => $this->faker->filePath(),
            'birth_certificate' => $this->faker->filePath(),

            // scheduling details
            'preferred_meeting_date' => $this->faker->date(),
            'preferred_meeting_time' => $this->faker->time(),

            'application_date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'processed_by' => User::factory(),
            'rejection_reason' => $this->faker->optional()->sentence(),
        
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
