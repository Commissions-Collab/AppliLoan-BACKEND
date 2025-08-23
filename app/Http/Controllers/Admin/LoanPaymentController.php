<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanPayment;
use Illuminate\Http\Request;

class LoanPaymentController extends Controller
{
    public function getLoanPayment()
    {
        $payments = LoanPayment::with([
            'loan:id,loan_application_id,loan_number',
            'loan.application:id,member_id,product_id',
            'loan.application.product:id,name',
            'loan.application.member:id,full_name',
            'receivedBy:id,email',
            'schedule:id,status'
        ])
            ->select(['loan_id', 'schedule_id', 'payment_date', 'amount_paid', 'remaining_balance', 'payment_method', 'receipt_number', 'received_by', 'notes'])
            ->latest()
            ->paginate(25);

        return response()->json([
            'payments' => $payments
        ]);
    }
}
