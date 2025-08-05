<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MemberExpense>
 */
class MemberExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $living = $this->faker->numberBetween(8000, 25000);
        $utilities = $this->faker->numberBetween(2000, 8000);
        $education = $this->faker->numberBetween(1000, 15000);
        $transport = $this->faker->numberBetween(1500, 8000);

        return [
            'member_id' => Member::factory(),
            'living_expenses' => $living,
            'utilities' => $utilities,
            'educational_expenses' => $education,
            'transportation_expenses' => $transport,
            'total_monthly_expenses' => $living + $utilities + $education + $transport,
        ];
    }
}
