<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\LoanPayment;
use Illuminate\Http\Request;

class LoanPaymentsController extends Controller
{
    public function displayPayMents()
    {
        $displayPayments = LoanPayment::select('id', 'loan_id','remaining_balance','payment_date','amount_paid')
            ->with(['loan:id,loan_application_id,loan_number,term_months,principal_amount,monthly_payment'])
            ->with(['loan.application:id,user_id,product_id,applied_amount'])
            ->with(['loan.application.user:id,email'])
            ->with(['loan.application.product:id,name'])
            ->latest()
            ->get();
        return response()->json($displayPayments);
    }
}
