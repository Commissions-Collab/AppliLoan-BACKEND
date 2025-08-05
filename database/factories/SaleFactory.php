<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sales>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $discount = $this->faker->randomFloat(2, 0, $subtotal * 0.2);
        $tax = $this->faker->randomFloat(2, 0, $subtotal * 0.12);
        $total = $subtotal - $discount + $tax;

        return [
            'sale_number' => strtoupper('SALE-' . Str::random(8)),
            'member_id' => Member::factory(),
            'sale_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'payment_method' => $this->faker->randomElement(['cash', 'credit', 'installment']),
            'payment_status' => $this->faker->randomElement(['paid', 'partial', 'unpaid']),
            'cashier_id' => 1,
            'notes' => $this->faker->optional()->sentence(5),
        ];
    }
}
