<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LoanApplication;
use App\Models\User;

class TestAppliancesQuery extends Seeder
{
    public function run()
    {
        try {
            // This is the exact query from Admin\AppliancesLoanController@index
            $applications = LoanApplication::with(
                'user:id,email,role',
                'product:id,name',
                'loan:id,loan_application_id,loan_number,monthly_payment,principal_amount,interest_rate,term_months,application_date,approval_date',
                'loanType:id,type_name,interest_rate'
            )
                ->whereHas('user', function ($q) {
                    $q->whereNotIn('role', ['admin', 'loan_clerk']);
                })
                ->select(['id', 'user_id', 'product_id', 'status', 'applied_amount', 'term_months', 'application_date', 'loan_type_id', 'user_name'])
                ->latest()
                ->get();

            echo "Query executed successfully!\n";
            echo "Found " . $applications->count() . " loan applications\n";

            return true;
        } catch (\Exception $e) {
            echo "Query failed with error: " . $e->getMessage() . "\n";
            return false;
        }
    }
}