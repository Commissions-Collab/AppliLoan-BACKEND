<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sales;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 10, 200);
        $discountRate = $this->faker->randomFloat(2, 0, 20); // e.g., 0% to 20% discount

        $lineTotal = $quantity * $unitPrice * ((100 - $discountRate) / 100);

        return [
            'sale_id' => Sales::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_rate' => $discountRate,
            'line_total' => $lineTotal,
        ];
    }
}
