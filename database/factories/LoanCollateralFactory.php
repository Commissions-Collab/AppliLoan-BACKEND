<?php

namespace Database\Factories;

use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanCollateral>
 */
class LoanCollateralFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            'Vehicle',
            'Real Estate',
            'Jewelry',
            'Animal',
            'Appliances',
            'Motorcycle',
            'Gadgets',
            'Equipment'
        ];

        return [
            'loan_id' => Loan::factory(),
            'collateral_type' => $this->faker->randomElement($types),
            'description' => $this->faker->sentence(12),
            'appraised_value' => $this->faker->randomFloat(2, 50000, 1000000),
            'location' => $this->faker->address(),
            'additional_details' => $this->faker->optional()->paragraph(),
            'status' => $this->faker->randomElement(['active', 'released', 'repossessed']),
        ];
    }
}
