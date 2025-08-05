<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryTransaction>
 */
class InventoryTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'transaction_type' => $this->faker->randomElement(['in', 'out', 'adjustment']),
            'quantity' => $this->faker->numberBetween(1, 100),
            'reference_type' => $this->faker->randomElement(['purchase', 'sale', 'adjustment', 'return']),
            'reference_id' => $this->faker->optional()->randomNumber(5),
            'transaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'notes' => $this->faker->optional(0.7)->sentence(5),
            'created_by' => User::factory(),
        ];
    }
}
