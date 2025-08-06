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
        $appliances = [
            'Refrigerator' => ['2-door', 'Single door', 'Side by side', 'French door'],
            'Washing Machine' => ['Top load', 'Front load', 'Twin tub'],
            'Television' => ['LED', 'Smart TV', 'Android TV'],
            'Air Conditioner' => ['Window type', 'Split type', 'Inverter'],
            'Microwave' => ['Basic', 'Convection', 'Grill'],
            'Rice Cooker' => ['Basic', 'Fuzzy logic', 'Induction'],
            'Blender' => ['Basic', 'High-speed', 'Immersion'],
        ];

        $appliance = fake()->randomKey($appliances);
        $type = fake()->randomElement($appliances[$appliance]);

        return [
            'category_id' => Category::factory(),
            'name' => $appliance . ' ' . $type . ' ' . fake()->bothify('##??'),
            'description' => fake()->paragraph(),
            'unit' => 'piece',
            'price' => fake()->numberBetween(5000, 50000),
            'stock_quantity' => fake()->numberBetween(5, 50),
            'image' => 'https://placehold.co/600x400',
            'status' => fake()->randomElement(['active', 'discontinued']),
        ];
    }
}
