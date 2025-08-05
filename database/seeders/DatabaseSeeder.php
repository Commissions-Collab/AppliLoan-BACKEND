<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,              // Required for members
            LoanTypeSeeder::class,         // Required before member existing loans and loan applications
            CategorySeeder::class,         // Required before product creation
            ProductSeeder::class,          // Products need categories
            MemberSeeder::class,           // Requires users, also may create existing loans
            LoanApplicationSeeder::class,  // Requires members and loan types
            LoanSeeder::class,             // Requires loan applications
            SaleSeeder::class,             // Requires members and products
        ]);
    }
}
