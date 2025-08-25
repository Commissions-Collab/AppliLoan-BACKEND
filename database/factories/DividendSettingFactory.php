<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DividendSetting>
 */
class DividendSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Randomly decide if the setting is approved
        $isApproved = $this->faker->boolean(70); // 70% chance of being true

        return [
            'year' => $this->faker->numberBetween(2022, 2025),
            'quarter' => $this->faker->optional(0.5)->numberBetween(1, 4), // 50% chance of being null (annual)
            'total_dividend_pool' => $this->faker->randomFloat(2, 50000, 500000),
            'distribution_method' => $this->faker->randomElement(['percentage_based', 'proportional', 'equal', 'hybrid']),
            'dividend_rate' => $this->faker->randomFloat(4, 0.02, 0.10), // Dividend rate between 2% and 10%
            'is_approved' => $isApproved,
            'approved_by' => $isApproved ? User::inRandomOrder()->first()->id ?? User::factory() : null,
            'approval_date' => $isApproved ? $this->faker->dateTimeThisYear() : null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
