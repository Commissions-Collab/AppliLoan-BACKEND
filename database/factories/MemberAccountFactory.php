<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MemberAccount>
 */
class MemberAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalShareCapital = $this->faker->numberBetween(5000, 50000);
        $currentShareCapital = $originalShareCapital + $this->faker->numberBetween(-1000, 20000);

        return [
            'member_id' => Member::factory(),
            'original_share_capital' => $originalShareCapital,
            'current_share_capital' => $currentShareCapital,
            'savings_balance' => $this->faker->numberBetween(1000, 100000),
            'regular_loan_balance' => $this->faker->numberBetween(0, 300000),
            'petty_cash_balance' => $this->faker->numberBetween(0, 10000),
        ];
    }
}
