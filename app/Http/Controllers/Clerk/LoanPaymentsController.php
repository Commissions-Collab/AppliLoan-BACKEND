<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\LoanPayment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $payment = LoanPayment::with(['loan.application.product', 'schedule'])->find($id);
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        DB::beginTransaction();
        try {
            $newStatus = $request->input('status');

            if ($newStatus === 'approved') {
                if (is_null($payment->schedule_id)) {
                    // First approved downpayment triggers stock decrement
                    $hasApprovedDownpayment = LoanPayment::where('loan_id', $payment->loan_id)
                        ->whereNull('schedule_id')
                        ->where('status', 'approved')
                        ->exists();

                    if (!$hasApprovedDownpayment) {
                        $application = optional($payment->loan)->application;
                        $productId = optional($application)->product_id;
                        if ($productId) {
                            $product = Product::whereKey($productId)->lockForUpdate()->first();
                            if (!$product || (int) $product->stock_quantity <= 0) {
                                throw ValidationException::withMessages([
                                    'stock' => 'Insufficient stock to approve down payment',
                                ]);
                            }
                            $product->decrement('stock_quantity');
                        }
                    }
                } else {
                    // Mark related schedule as paid
                    if ($payment->schedule) {
                        $payment->schedule->status = 'paid';
                        $payment->schedule->save();
                    }
                }
            }

            $payment->status = $newStatus;
            $payment->save();

            DB::commit();
            return response()->json([
                'message' => 'Payment status updated successfully',
                'payment' => $payment,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            $status = $e instanceof ValidationException ? 422 : 500;
            return response()->json([
                'message' => 'Failed to update payment status',
                'error' => $e->getMessage(),
            ], $status);
        }
    }
}
