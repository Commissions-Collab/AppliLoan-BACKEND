<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Rice & Grains', 'Canned Goods', 'Beverages', 'Snacks & Sweets',
            'Personal Care', 'Household Items', 'School Supplies', 'Hardware',
            'Frozen Goods', 'Fresh Produce'
        ];
        
        return [
            'name' => $this->faker->randomElement($categories),
            'description' => $this->faker->sentence(8),
        ];
    }
}
