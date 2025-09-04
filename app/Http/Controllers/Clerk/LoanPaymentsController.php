<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\LoanPayment;
use Illuminate\Http\Request;

class LoanPaymentsController extends Controller
{
    public function displayPayMents()
    {
        $displayPayments = LoanPayment::select('id', 'loan_id','remaining_balance','payment_date')
            ->with('loan:id,loan_application_id,term_months')
            ->with('loan.application:id,member_id,product_id,applied_amount')
            ->with('loan.application.member:id,full_name')
            ->with('loan.application.product:id,name')
            ->get();
        return response()->json($displayPayments);
    }
}
