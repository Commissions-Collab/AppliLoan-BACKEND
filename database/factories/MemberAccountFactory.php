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
    public function definition()
    {
        $originalShare = fake()->numberBetween(1000, 10000);
        
        return [
            'member_id' => Member::factory(),
            'original_share_capital' => $originalShare,
            'current_share_capital' => $originalShare,
            'savings_balance' => fake()->numberBetween(500, 50000),
            'regular_loan_balance' => fake()->numberBetween(0, 100000),
            'petty_cash_balance' => fake()->numberBetween(0, 5000),
        ];
    }
}
