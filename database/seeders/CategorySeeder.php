<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\LoanType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create loan types
        LoanType::create([
            'type_name' => 'Appliance Loan',
            'description' => 'Loans for purchasing appliances',
            'interest_rate' => 5.00,
            'max_amount' => 50000.00,
            'min_amount' => 1000.00,
            'max_term_months' => 24,
            'collateral_required' => false,
        ]);

        // Create categories
        $categories = [
            ['name' => 'Kitchen Appliances', 'description' => 'Refrigerators, microwaves, rice cookers'],
            ['name' => 'Laundry Equipment', 'description' => 'Washing machines, dryers'],
            ['name' => 'Electronics', 'description' => 'Televisions, audio systems'],
            ['name' => 'Climate Control', 'description' => 'Air conditioners, fans'],
            ['name' => 'Small Appliances', 'description' => 'Blenders, coffee makers, toasters'],
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }
    }
}
