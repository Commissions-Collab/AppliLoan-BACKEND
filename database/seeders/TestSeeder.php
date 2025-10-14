<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\LoanApplication;
use App\Models\LoanType;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;

class TestSeeder extends Seeder
{
    public function run()
    {
        // Create basic data first
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_verified' => true,
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'user@test.com'],
            [
                'password' => Hash::make('password'),
                'role' => 'member',
                'is_verified' => true,
            ]
        );

        // Create category and product
        $category = Category::create([
            'name' => 'Test Category',
            'description' => 'Test description'
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'description' => 'Test product description',
            'unit' => 'pcs',
            'price' => 5000.00,
            'stock_quantity' => 10,
            'status' => 'active'
        ]);

        // Create loan type
        $loanType = LoanType::create([
            'type_name' => 'Test Loan',
            'description' => 'Test loan description',
            'interest_rate' => 5.0,
            'max_amount' => 100000,
            'max_term_months' => 24
        ]);

        // Create loan application with user_id
        LoanApplication::create([
            'user_id' => $user->id,
            'loan_type_id' => $loanType->id,
            'product_id' => $product->id,
            'user_name' => $user->name,
            'applied_amount' => 5000.00,
            'term_months' => 12,
            'phone' => '09123456789',
            'address' => 'Test Address',
            'application_date' => now()->format('Y-m-d'),
            'status' => 'pending'
        ]);

        echo "Test seeding completed successfully!\n";
    }
}