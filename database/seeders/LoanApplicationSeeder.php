<?php

namespace Database\Seeders;

use App\Models\LoanApplication;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoanApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = Member::all();
        $loanTypes = LoanType::all();
        $products = Product::all();
        $processors = User::whereIn('role', ['admin', 'loan_clerk'])->get();

        foreach ($members->take(30) as $member) {
            if (rand(1, 100) <= 70) {
                $applicationsCount = rand(1, 3);

                for ($i = 0; $i < $applicationsCount; $i++) {
                    $loanType = $loanTypes->random();
                    $product = $products->random();
                    $status = collect(['pending', 'approved', 'rejected'])->random();

                    LoanApplication::create([
                        'member_id' => $member->id,
                        'loan_type_id' => $loanType->id,
                        'product_id' => $product->id, // correct foreign key
                        'applied_amount' => rand(10000, min(500000, ($member->monthly_income ?? 0) * 3)),
                        'term_months' => collect([12, 18, 24, 36, 48])->random(),
                        'application_date' => now()->subDays(rand(1, 180)),
                        'status' => $status,
                        'processed_by' => $status !== 'pending' ? optional($processors->random())->id : null,
                    ]);
                }
            }
        }
    }
}
