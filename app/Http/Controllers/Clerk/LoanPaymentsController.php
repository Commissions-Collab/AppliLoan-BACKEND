<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    // display all payments for clerk
    public function index()
    {
        $payments = LoanPayment::with(['loan', 'schedule', 'receivedBy'])->orderByDesc('created_at')->get();
        return response()->json([
            'payments' => $payments,
        ], 200);

    }

    // show specific payment details
    public function show($id)
    {
        $payment = LoanPayment::with(['loan', 'schedule', 'receivedBy'])->find($id);    
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
        return response()->json([
            'payment' => $payment,
        ], 200);
    }

    // update payment status
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,rejected',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $payment = LoanPayment::find($id);
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
        $payment->status = $request->input('status');
        $payment->save();
        return response()->json([
            'message' => 'Payment status updated successfully',
            'payment' => $payment,
        ], 200);
    }
}
