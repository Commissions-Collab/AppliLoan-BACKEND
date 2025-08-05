<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $products = [
            ['name' => 'White Rice 25kg', 'unit' => 'sack', 'price' => 1200],
            ['name' => 'Corned Beef 150g', 'unit' => 'can', 'price' => 45],
            ['name' => 'Instant Coffee 100g', 'unit' => 'pack', 'price' => 85],
            ['name' => 'Laundry Soap 200g', 'unit' => 'bar', 'price' => 25],
            ['name' => 'Cooking Oil 1L', 'unit' => 'bottle', 'price' => 65],
            ['name' => 'Sugar 1kg', 'unit' => 'pack', 'price' => 55],
            ['name' => 'Salt 500g', 'unit' => 'pack', 'price' => 15],
            ['name' => 'Sardines 155g', 'unit' => 'can', 'price' => 22],
        ];

        $product = $this->faker->randomElement($products);

        return [
            'category_id' => Category::factory(),
            'name' => $product['name'],
            'description' => $this->faker->sentence(6),
            'unit' => $product['unit'],
            'price' => $product['price'],
            'stock_quantity' => $this->faker->numberBetween(10, 500),
            'image' => $this->faker->imageUrl(640, 480, 'products', true),
            'status' => $this->faker->randomElement(['active', 'discontinued']),
        ];
    }
}
