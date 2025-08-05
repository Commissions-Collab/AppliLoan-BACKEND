<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Rice & Grains',
                'description' => 'Rice, wheat, corn and other grain products',
            ],
            [
                'name' => 'Canned Goods',
                'description' => 'Preserved food items in cans and packages',
            ],
            [
                'name' => 'Beverages',
                'description' => 'Soft drinks, juices, coffee, and other beverages',
            ],
            [
                'name' => 'Snacks & Sweets',
                'description' => 'Chips, crackers, candies, and sweet treats',
            ],
            [
                'name' => 'Personal Care',
                'description' => 'Soaps, shampoos, toothpaste, and hygiene products',
            ],
            [
                'name' => 'Household Items',
                'description' => 'Cleaning supplies, detergents, and home essentials',
            ],
            [
                'name' => 'School Supplies',
                'description' => 'Notebooks, pens, pencils, and educational materials',
            ],
            [
                'name' => 'Hardware',
                'description' => 'Tools, nails, screws, and construction materials',
            ],
            [
                'name' => 'Frozen Goods',
                'description' => 'Frozen meats, vegetables, and ice cream',
            ],
            [
                'name' => 'Fresh Produce',
                'description' => 'Fresh fruits, vegetables, and dairy products',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
