<?php

namespace Database\Seeders;

use App\Models\LoanType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoanTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $loanTypes = [
            [
                'type_name' => 'Regular Loan',
                'description' => 'General purpose loan for members with flexible terms',
                'min_amount' => 10000,
                'max_amount' => 500000,
                'interest_rate' => 12.00,
                'max_term_months' => 60,
                'collateral_required' => false,
            ],
            [
                'type_name' => 'Appliance Loan',
                'description' => 'Loan for purchasing home appliances and gadgets',
                'min_amount' => 5000,
                'max_amount' => 200000,
                'interest_rate' => 14.00,
                'max_term_months' => 48,
                'collateral_required' => true,
            ],
        ];

        foreach ($loanTypes as $loanType) {
            LoanType::create($loanType);
        }
    }
}
